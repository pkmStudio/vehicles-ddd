<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Modification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification\UpsertModificationFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationTdRowDTO;
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
 * Use-case: создать/обновить модификацию из строки импорта (приведение к виду TD).
 * ТС должно уже существовать (резолв по ms_id) — иначе сценарий выбрасывает reference error,
 * а адаптер отражает это в отчёте об ошибках.
 */
final readonly class UpsertModificationFromTdRowService implements UpsertModificationFromTdRowServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-modification-import';

    /**
     * Инициализирует порты сценария upsert модификации.
     *
     * Шаги:
     * 1) Сохранить command записи модификации.
     * 2) Сохранить factory валидации и сборки `ModificationData`.
     * 3) Сохранить repositories для vehicle lookup и проверки существующей модификации.
     */
    public function __construct(
        private ModificationCommandInterface $command,
        private ModificationDataFactoryInterface $factory,
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
        private ModificationWritePolicy $writePolicy,
    ) {}

    /**
     * Создает или обновляет модификацию из command import row.
     *
     * Шаги:
     * 1) Найти vehicle по `ms_id`; если он отсутствует — выбросить reference error.
     * 2) Передать typed command DTO и resolved vehicle id в factory.
     * 3) Валидировать и преобразовать строку в `ModificationData`.
     * 4) Найти существующую модификацию по `mod_id` и type.
     * 5) Применить provider-aware write policy.
     * 6) Выполнить create или update через command.
     * 7) Опубликовать catalog mutation event о создании или обновлении.
     *
     * @throws ImportRowValidationException
     * @throws ImportRowReferenceNotFoundException
     */
    public function upsertFromRow(ModificationTdRowDTO $row): ModificationData
    {
        $vehicle = $this->vehicles->findByMsId($row->msId);
        if (! $vehicle) {
            throw ImportRowReferenceNotFoundException::withMessage("ТС ms_id={$row->msId} не найдено.");
        }

        $data = $this->factory->makeFromTdRow($row->withVehicleId((int) $vehicle->id));
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
