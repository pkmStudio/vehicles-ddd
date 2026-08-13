<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\VehicleImportWritePolicyInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleImportWriteContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\VehicleImportSourceEnum;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;

/**
 * Use-case: создать/обновить ТС из строки авторитетного импорта (приведение к виду TD).
 * Производитель должен уже существовать (резолв по mfa_id) — иначе сценарий сигналит null,
 * адаптер отражает это в отчёте об ошибках.
 */
final readonly class UpsertVehicleFromTdRowService implements UpsertVehicleFromTdRowServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-vehicle-import';

    /**
     * Инициализирует порты сценария upsert автомобиля из TecDoc row.
     *
     * Шаги:
     * 1) Сохранить vehicle command и factory.
     * 2) Сохранить repositories производителя и автомобиля.
     * 3) Сохранить write policy, которая применяет provider-aware правила обновления.
     */
    public function __construct(
        private VehicleCommandInterface $command,
        private VehicleDataFactoryInterface $factory,
        private ManufacturerRepositoryInterface $manufacturers,
        private VehicleRepositoryInterface $vehicles,
        private VehicleImportWritePolicyInterface $writePolicy,
    ) {}

    /**
     * Создает или обновляет автомобиль из авторитетной TecDoc строки.
     *
     * Шаги:
     * 1) Найти производителя по `mfa_id`; если он отсутствует — вернуть null.
     * 2) Собрать raw row array и преобразовать его в `VehicleData`.
     * 3) Найти существующий vehicle по `ms_id`.
     * 4) Применить TecDoc write context через write policy.
     * 5) Выполнить create или update через command.
     * 6) Опубликовать catalog mutation event о создании или обновлении.
     *
     * @return VehicleData|null null, если производитель с таким mfa_id не найден
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(VehicleTdRowDTO $row): ?VehicleData
    {
        $manufacturer = $this->manufacturers->findByMfaId($row->mfaId);

        if (! $manufacturer) {
            return null;
        }

        $data = $this->factory->make([
            'ms_id' => $row->msId,
            'mfa_id' => $row->mfaId,
            'name' => $row->name,
            'type' => $row->type,
            'type_carcase' => $row->typeCarcase,
            'generation' => $row->generation,
            'generation_year_from' => $row->generationYearFrom,
            'generation_year_to' => $row->generationYearTo,
            'manufacturer_id' => $manufacturer->id,
            'provider' => ProviderEnum::TD->value,
        ]);

        $existing = $this->vehicles->findByMsId($data->msId);
        $writeContext = new VehicleImportWriteContextDTO(
            source: VehicleImportSourceEnum::TecDocCommand,
            sourceProvider: ProviderEnum::TD,
            operationId: self::OPERATION_ID,
            msId: $data->msId,
            rowIdentifier: (string) $row->msId,
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
            ? new VehicleCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $vehicle->toArray())
            : new VehicleUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $vehicle->toArray()));

        return $vehicle;
    }
}
