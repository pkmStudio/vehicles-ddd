<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\VehicleImportWritePolicyInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleImportWriteContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\VehicleImportSourceEnum;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;

/**
 * Use-case: создать/обновить ТС из строки ручного листа.
 * Оркестрация: резолв производителя → валидация → запись. Персистентность — только через порты
 * (Repository/Command), прямого Eloquent в Application нет.
 */
final readonly class UpsertVehicleFromSheetService implements UpsertVehicleFromSheetServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string MANUFACTURER_OPERATION_ID = 'vehicles-manufacturer-import';

    private const string VEHICLE_OPERATION_ID = 'vehicles-vehicle-import';

    /**
     * Инициализирует порты сценария upsert автомобиля из ручного sheet row.
     *
     * Шаги:
     * 1) Сохранить vehicle command/factory/repository зависимости.
     * 2) Сохранить manufacturer factory/repository/command для inline-создания производителя.
     * 3) Сохранить write policy, которая применяет provider-aware правила обновления.
     */
    public function __construct(
        private VehicleCommandInterface $command,
        private VehicleDataFactoryInterface $factory,
        private ManufacturerDataFactoryInterface $manufacturerFactory,
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
        private ManufacturerCommandInterface $manufacturerCommand,
        private VehicleImportWritePolicyInterface $writePolicy,
    ) {}

    /**
     * Создает или обновляет автомобиль из строки ручного import-листа.
     *
     * Шаги:
     * 1) Подготовить минимальные отрицательные ids для новых manufacturer/vehicle записей.
     * 2) Если указан parent `ms_id` — найти parent vehicle id.
     * 3) Разрешить или создать производителя для строки.
     * 4) Собрать raw row array и преобразовать его в `VehicleData`.
     * 5) Найти существующий vehicle по `ms_id`.
     * 6) Применить provider-aware write policy.
     * 7) Выполнить create или update через command и опубликовать event.
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(VehicleSheetRowDTO $row): VehicleData
    {
        $minMfaId = min($this->manufacturers->findMinMfaId()?->mfaId ?? 0, 0);
        $minMsId = min($this->vehicles->findMinMsId()?->msId ?? 0, 0);

        $parentId = $row->parentMsId !== null
            ? $this->vehicles->findByMsId($row->parentMsId)?->id
            : null;

        [$mfaId, $manufacturerId] = $this->resolveManufacturer($minMfaId, $row);
        $msId = $row->msId ?? --$minMsId;

        $data = $this->factory->make([
            'ms_id' => $msId,
            'mfa_id' => $mfaId,
            'name' => $row->name,
            'type' => $row->type,
            'type_carcase' => $row->typeCarcase,
            'steering_type' => $row->steeringType,
            'generation' => $row->generation,
            'generation_short' => $row->generationShort,
            'localized_name' => $row->localizedName,
            'excel_table_id' => $row->excelTableId,
            'provider' => $row->provider,
            'generation_year_from' => $row->generationYearFrom,
            'generation_year_to' => $row->generationYearTo,
            'is_allow' => $row->isAllow,
            'manufacturer_id' => $manufacturerId,
            'parent_id' => $parentId,
        ]);

        $existing = $this->vehicles->findByMsId($data->msId);
        $writeContext = new VehicleImportWriteContextDTO(
            source: VehicleImportSourceEnum::ManualSheet,
            sourceProvider: $data->provider,
            operationId: self::VEHICLE_OPERATION_ID,
            msId: $data->msId,
            rowIdentifier: (string) ($row->msId ?? $row->name ?? $data->msId),
        );
        $writeData = $this->writePolicy->apply(
            incoming: $data,
            existing: $existing,
            context: $writeContext,
        );

        $vehicle = $existing === null
            ? $this->command->create($writeData)
            : $this->command->updateByMsId($writeData);

        event($existing === null
            ? new VehicleCreated(self::IMPORT_USER_ID, self::VEHICLE_OPERATION_ID, $vehicle->toArray())
            : new VehicleUpdated(self::IMPORT_USER_ID, self::VEHICLE_OPERATION_ID, $vehicle->toArray()));

        return $vehicle;
    }

    /**
     * Разрешает производителя для строки ручного import-листа.
     *
     * Шаги:
     * 1) Если `mfa_id` не указан — искать производителя по имени.
     * 2) Если `mfa_id` указан — искать производителя по нему.
     * 3) Если производитель не найден — назначить новый отрицательный `mfa_id`.
     * 4) Собрать `ManufacturerData` с provider OD и создать производителя через command.
     * 5) Опубликовать event создания производителя.
     * 6) Вернуть пару `[mfa_id, manufacturer_id]`.
     *
     * @return array{0: int, 1: int} [mfa_id, manufacturer_id]
     */
    private function resolveManufacturer(int &$minMfaId, VehicleSheetRowDTO $row): array
    {
        $manufacturer = $row->mfaId === null
            ? $this->manufacturers->findByName((string) $row->manufacturerName)
            : $this->manufacturers->findByMfaId($row->mfaId);

        if (! $manufacturer) {
            $mfaId = $row->mfaId ?? --$minMfaId;

            $manufacturerData = $this->manufacturerFactory->make([
                'mfa_id' => $mfaId,
                'name' => $row->manufacturerName,
                'provider' => ProviderEnum::OD->value,
            ]);

            $manufacturer = $this->manufacturerCommand->create($manufacturerData);

            event(new ManufacturerCreated(
                self::IMPORT_USER_ID,
                self::MANUFACTURER_OPERATION_ID,
                $manufacturer->toArray(),
            ));
        }

        return [$manufacturer->mfaId, $manufacturer->id];
    }
}
