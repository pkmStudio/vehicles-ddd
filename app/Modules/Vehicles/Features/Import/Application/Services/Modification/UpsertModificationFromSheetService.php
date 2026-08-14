<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Modification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification\UpsertModificationFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\ModificationEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\ModificationWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationUpdated;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\ModificationWritePolicy;

/**
 * Use-case manager импорта модификаций: резолвит vehicle/type и пишет с provider-policy.
 */
final readonly class UpsertModificationFromSheetService implements UpsertModificationFromSheetServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-modification-manager-import';

    /**
     * Инициализирует порты manager upsert модификации.
     */
    public function __construct(
        private ModificationCommandInterface $command,
        private ModificationDataFactoryInterface $factory,
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
        private ModificationWritePolicy $writePolicy,
    ) {}

    /**
     * Создает или обновляет модификацию из manager import-листа.
     *
     * Шаги:
     * 1) Найти vehicle по ms_id; если не найден — выбросить reference error.
     * 2) Рассчитать type из найденного vehicle и проверить пришедший type, если он заполнен.
     * 3) Если mod_id пустой — назначить следующий отрицательный mod_id.
     * 4) Собрать ModificationData из typed row DTO с provider OD для manager-строки.
     * 5) Создать OD-запись или обновить существующую через manager-policy command.
     *
     * @throws ImportRowValidationException
     * @throws ImportRowReferenceNotFoundException
     */
    public function upsertFromRow(ModificationSheetRowDTO $row): ModificationData
    {
        $vehicle = $this->vehicles->findByMsId($row->msId);

        if ($vehicle === null) {
            throw ImportRowReferenceNotFoundException::withMessage("ТС ms_id={$row->msId} не найдено.");
        }

        $calculatedType = $vehicle->type->value;
        if ($row->type !== null && $row->type !== $calculatedType) {
            throw ImportRowValidationException::fromMessage("Поле type={$row->type} не совпадает с type={$calculatedType} автомобиля.");
        }

        $minModId = min($this->modifications->findMinModId()?->modId ?? 0, 0);
        $modId = $row->modId ?? --$minModId;

        $data = $this->factory->makeFromManagerSheetRow(
            row: $row,
            modId: $modId,
            type: $calculatedType,
            vehicleId: (int) $vehicle->id,
        );

        $existing = $this->modifications->findByModIdAndType($data->modId, $data->type->value);
        try {
            $writeResult = $this->writePolicy->apply(
                incoming: ModificationWritePolicyResultDTO::fromArray($data->toArray()),
                existing: $existing === null ? null : ModificationWritePolicyResultDTO::fromArray($existing->toArray()),
                sourceProvider: $data->provider,
            );
        } catch (ProviderOwnershipException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }
        $writeData = ModificationData::from($writeResult->toArray());

        $modification = $existing === null
            ? $this->command->create($writeData)
            : $this->command->update($writeData);

        $payload = new ModificationEventPayloadDTO(
            id: (int) $modification->id,
            modId: $modification->modId,
            type: $modification->type,
            vehicleId: $modification->vehicleId,
            msId: $modification->msId,
            provider: $modification->provider,
            yearFrom: $modification->yearFrom,
            description: $modification->description,
            powerPs: $modification->powerPs,
            powerKw: $modification->powerKw,
            engineType: $modification->engineType,
            yearTo: $modification->yearTo,
            descriptionShort: $modification->descriptionShort,
            localizedName: $modification->localizedName,
            gearType: $modification->gearType,
            driveType: $modification->driveType,
            brakeSystemType: $modification->brakeSystemType,
            numberOfCylinders: $modification->numberOfCylinders,
            capacityLt: $modification->capacityLt,
            allowChangeFields: $modification->allowChangeFields,
        );

        event($existing === null
            ? new ModificationCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $payload)
            : new ModificationUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $payload));

        return $modification;
    }
}
