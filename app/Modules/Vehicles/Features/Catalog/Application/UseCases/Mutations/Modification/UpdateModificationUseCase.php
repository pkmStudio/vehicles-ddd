<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\UpdateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\ModificationEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\ModificationWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationUpdated;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\ModificationWritePolicy;
use Throwable;

/**
 * Оркестрирует сценарий мутации модификаций из внешнего сообщения.
 */
final readonly class UpdateModificationUseCase
{
    /**
     * Получает порты update modification workflow.
     *
     * Шаги:
     * 1) Принять repositories для поиска modification, vehicle и engine.
     * 2) Принять commands для записи modification, engine и engine_modification связей.
     * 3) Принять cache/result сервисы для идемпотентности и публикации результата.
     */
    public function __construct(
        private ModificationRepositoryInterface $modifications,
        private EngineRepositoryInterface $engines,
        private VehicleRepositoryInterface $vehicles,
        private ModificationCommandInterface $command,
        private EngineModificationCommandInterface $engineModifications,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
        private ModificationWritePolicy $writePolicy,
    ) {}

    /**
     * Выполняет сценарий мутации модификаций.
     *
     * Шаги:
     * 1) Зафиксировать operationId для идемпотентности.
     * 2) Проверить бизнес-ограничения операции.
     * 3) Выполнить запись через Command и отправить доменное событие.
     * 4) Опубликовать унифицированный результат мутации.
     */
    public function execute(UpdateModificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $existing = $this->modifications->findByModIdAndType(
                modId: $request->modId,
                type: $request->type->value,
            );
            if ($existing === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Update,
                    externalId: $request->modId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            $vehicle = $this->vehicles->findByMsId($request->msId);
            if ($vehicle === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Update,
                    externalId: $request->modId,
                    reason: CatalogMutationRejectReasonEnum::VehicleNotFound,
                );
            }

            $engines = [];
            if ($request->syncEngines) {
                $engines = $this->existingEngines($request->engines);
                if ($engines === null) {
                    return $this->results->rejected(
                        userId: $request->userId,
                        operationId: $request->operationId,
                        entity: CatalogEntityEnum::Modification,
                        operation: CatalogMutationOperationEnum::Update,
                        externalId: $request->modId,
                        reason: CatalogMutationRejectReasonEnum::NotFound,
                    );
                }
            }

            $modificationData = new ModificationData(
                modId: $request->modId,
                type: $request->type,
                vehicleId: (int) $vehicle->id,
                msId: $request->msId,
                yearFrom: $request->yearFrom,
                yearTo: $request->yearTo,
                description: $request->description,
                descriptionShort: $request->descriptionShort,
                localizedName: $request->localizedName,
                powerPs: $request->powerPs,
                powerKw: $request->powerKw,
                engineType: $request->engineType,
                gearType: $request->gearType,
                driveType: $request->driveType,
                brakeSystemType: $request->brakeSystemType,
                numberOfCylinders: $request->numberOfCylinders,
                capacityLt: $request->capacityLt,
                provider: $request->provider,
                allowChangeFields: $request->allowChangeFields,
                id: $existing->id,
            );

            $writeResult = $this->writePolicy->apply(
                incoming: ModificationWritePolicyResultDTO::fromArray($modificationData->toArray()),
                existing: ModificationWritePolicyResultDTO::fromArray($existing->toArray()),
                sourceProvider: $request->provider,
            );

            $modification = $this->command->update(ModificationData::from($writeResult->toArray()));

            if ($request->syncEngines) {
                $this->engineModifications->syncForModification(
                    modification: $modification,
                    engines: $engines,
                );
            }

            $payload = new ModificationEventPayloadDTO(
                id: (int) $modification->id,
                modId: $modification->modId,
                type: $modification->type,
                vehicleId: $modification->vehicleId,
                msId: $modification->msId,
                provider: $modification->provider,
                yearFrom: $modification->yearFrom,
                yearTo: $modification->yearTo,
                description: $modification->description,
                descriptionShort: $modification->descriptionShort,
                localizedName: $modification->localizedName,
                powerPs: $modification->powerPs,
                powerKw: $modification->powerKw,
                engineType: $modification->engineType,
                gearType: $modification->gearType,
                driveType: $modification->driveType,
                brakeSystemType: $modification->brakeSystemType,
                numberOfCylinders: $modification->numberOfCylinders,
                capacityLt: $modification->capacityLt,
                allowChangeFields: $modification->allowChangeFields,
            );

            event(new ModificationUpdated(
                userId: $request->userId,
                operationId: $request->operationId,
                modification: $payload,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Modification,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $modification->modId,
                recordId: $modification->id,
            );
        } catch (ProviderOwnershipException $e) {
            return $this->results->rejected(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Modification,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $request->modId,
                reason: CatalogMutationRejectReasonEnum::ProviderOwnershipConflict,
                errors: $e->errors(),
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Modification,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $request->modId,
            );

            throw $e;
        }
    }

    /**
     * Возвращает только существующие двигатели, перечисленные во входящей modification mutation.
     *
     * Шаги:
     * 1) Для каждого engine request требовать внешний `eng_id`.
     * 2) Найти существующий двигатель по `eng_id`.
     * 3) Вернуть `null`, если хотя бы один двигатель не найден.
     * 4) Вернуть список существующих двигателей для синхронизации pivot-связей.
     *
     * @param  list<ModificationEngineRequestDTO>  $requests
     * @return list<EngineData>|null
     */
    private function existingEngines(array $requests): ?array
    {
        $engines = [];

        foreach ($requests as $request) {
            $engine = $this->engines->findByEngId($request->engId);
            if ($engine === null) {
                return null;
            }

            $engines[] = $engine;
        }

        return $engines;
    }
}
