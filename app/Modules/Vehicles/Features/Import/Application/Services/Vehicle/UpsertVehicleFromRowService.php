<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\ManufacturerEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\VehicleEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\VehicleWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\VehicleWritePolicy;

/**
 * Use-case: создать/обновить ТС из нормализованной строки ручного импорта.
 * Оркестрация: резолв производителя → валидация → запись. Персистентность — только через порты
 * (Repository/Command), прямого Eloquent в Application нет.
 */
final readonly class UpsertVehicleFromRowService implements UpsertVehicleFromRowServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string MANUFACTURER_OPERATION_ID = 'vehicles-manufacturer-import';

    private const string VEHICLE_OPERATION_ID = 'vehicles-vehicle-import';

    /**
     * Инициализирует порты сценария upsert автомобиля из ручной import row.
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
        private VehicleWritePolicy $writePolicy,
    ) {}

    /**
     * Создает или обновляет автомобиль из строки ручного импорта.
     *
     * Шаги:
     * 1) Подготовить минимальные отрицательные ids для новых manufacturer/vehicle записей.
     * 2) Если указан parent `ms_id` — найти parent vehicle id.
     * 3) Разрешить или создать производителя для строки.
     * 4) Передать typed row DTO и resolved ids в factory.
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

        $data = $this->factory->makeFromSheetRow(
            row: $row,
            msId: $msId,
            mfaId: $mfaId,
            manufacturerId: $manufacturerId,
            parentId: $parentId,
        );

        $existing = $this->vehicles->findByMsId($data->msId);
        try {
            $writeResult = $this->writePolicy->apply(
                incoming: VehicleWritePolicyResultDTO::fromArray($data->toArray()),
                existing: $existing === null ? null : VehicleWritePolicyResultDTO::fromArray($existing->toArray()),
                sourceProvider: $data->provider,
            );
        } catch (ProviderOwnershipException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }
        $writeData = VehicleData::from($writeResult->toArray());

        $vehicle = $existing === null
            ? $this->command->create($writeData)
            : $this->command->update($writeData);

        $payload = new VehicleEventPayloadDTO(
            id: (int) $vehicle->id,
            msId: $vehicle->msId,
            mfaId: $vehicle->mfaId,
            manufacturerId: $vehicle->manufacturerId,
            name: $vehicle->name,
            type: $vehicle->type,
            steeringType: $vehicle->steeringType,
            typeCarcase: $vehicle->typeCarcase,
            provider: $vehicle->provider,
            generation: $vehicle->generation,
            generationYearFrom: $vehicle->generationYearFrom,
            isAllow: $vehicle->isAllow,
            generationYearTo: $vehicle->generationYearTo,
            parentId: $vehicle->parentId,
            parentMsId: $vehicle->parentMsId ?? null,
            excelTableId: $vehicle->excelTableId,
            localizedName: $vehicle->localizedName,
            generationShort: $vehicle->generationShort,
        );

        event($existing === null
            ? new VehicleCreated(self::IMPORT_USER_ID, self::VEHICLE_OPERATION_ID, $payload)
            : new VehicleUpdated(self::IMPORT_USER_ID, self::VEHICLE_OPERATION_ID, $payload));

        return $vehicle;
    }

    /**
     * Разрешает производителя для строки ручного импорта.
     *
     * Шаги:
     * 1) Если `mfa_id` не указан — искать производителя по имени.
     * 2) Если `mfa_id` указан — искать производителя по нему.
     * 3) Если производитель не найден — назначить новый отрицательный `mfa_id`.
     * 4) Собрать `ManufacturerData` из typed row DTO и создать производителя через command.
     * 5) Опубликовать event создания производителя.
     * 6) Вернуть пару `[mfa_id, manufacturer_id]`.
     *
     * @return array{0: int, 1: int} [mfa_id, manufacturer_id]
     */
    private function resolveManufacturer(int &$minMfaId, VehicleSheetRowDTO $row): array
    {
        $manufacturer = $row->mfaId === null
            ? $this->manufacturers->findByName($row->manufacturerName)
            : $this->manufacturers->findByMfaId($row->mfaId);

        if (! $manufacturer) {
            $mfaId = $row->mfaId ?? --$minMfaId;
            $manufacturerData = $this->manufacturerFactory->makeFromVehicleSheetRow($row, $mfaId);
            $manufacturer = $this->manufacturerCommand->create($manufacturerData);
            $payload = new ManufacturerEventPayloadDTO(
                id: (int) $manufacturer->id,
                mfaId: $manufacturer->mfaId,
                name: $manufacturer->name,
                provider: $manufacturer->provider,
            );

            event(new ManufacturerCreated(
                self::IMPORT_USER_ID,
                self::MANUFACTURER_OPERATION_ID,
                $payload,
            ));
        }

        return [$manufacturer->mfaId, $manufacturer->id];
    }
}
