<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\VehicleEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\VehicleWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\VehicleWritePolicy;

/**
 * Use-case: создать/обновить ТС из строки авторитетного импорта (приведение к виду TD).
 * Производитель должен уже существовать (резолв по mfa_id) — иначе сценарий выбрасывает
 * ошибку отсутствующей ссылки, а адаптер отражает это в отчёте об ошибках.
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
        private VehicleWritePolicy $writePolicy,
    ) {}

    /**
     * Создает или обновляет автомобиль из авторитетной TecDoc строки.
     *
     * Шаги:
     * 1) Найти производителя по `mfa_id`; если он отсутствует — выбросить reference error.
     * 2) Передать typed row DTO и resolved manufacturer id в factory.
     * 3) Найти существующий vehicle по `ms_id`.
     * 4) Применить TecDoc write context через write policy.
     * 5) Выполнить create или update через command.
     * 6) Опубликовать catalog mutation event о создании или обновлении.
     *
     * @throws ImportRowValidationException
     * @throws ImportRowReferenceNotFoundException
     */
    public function upsertFromRow(VehicleTdRowDTO $row): VehicleData
    {
        $manufacturer = $this->manufacturers->findByMfaId($row->mfaId);
        if (! $manufacturer) {
            throw ImportRowReferenceNotFoundException::withMessage("Производитель mfa_id={$row->mfaId} не найден.");
        }

        $data = $this->factory->makeFromTdRow($row, (int) $manufacturer->id);
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
            generationYearTo: $vehicle->generationYearTo,
            parentId: $vehicle->parentId,
            parentMsId: $vehicle->parentMsId ?? null,
            excelTableId: $vehicle->excelTableId,
            localizedName: $vehicle->localizedName,
            generationShort: $vehicle->generationShort,
            isAllow: $vehicle->isAllow,
        );

        event($existing === null
            ? new VehicleCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $payload)
            : new VehicleUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $payload));

        return $vehicle;
    }
}
