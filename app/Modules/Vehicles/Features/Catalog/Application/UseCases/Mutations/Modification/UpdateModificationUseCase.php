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
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationEngineLinkDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\UpdateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\ModificationEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\ModificationWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
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

            $engineLinks = [];
            if ($request->syncEngines) {
                $currentTdEngineIds = $existing->id === null
                    ? []
                    : $this->modifications->findTdEngineExternalIdsByModificationId($existing->id)->all();

                $engineLinks = $this->existingEngineLinks(
                    requests: $request->engines,
                    currentTdEngineIds: $currentTdEngineIds,
                );
                if ($engineLinks === null) {
                    return $this->results->rejected(
                        userId: $request->userId,
                        operationId: $request->operationId,
                        entity: CatalogEntityEnum::Modification,
                        operation: CatalogMutationOperationEnum::Update,
                        externalId: $request->modId,
                        reason: CatalogMutationRejectReasonEnum::NotFound,
                    );
                }

                $this->writePolicy->assertTdModificationEngineLinksUnchanged(
                    existing: ModificationWritePolicyResultDTO::fromArray($existing->toArray()),
                    currentTdEngineIds: $currentTdEngineIds,
                    incomingTdEngineIds: $this->tdRelationEngineIds($engineLinks),
                );
            }

            $modificationData = new ModificationData(
                modId: $request->modId,
                type: $request->type,
                vehicleId: (int) $vehicle->id,
                msId: $request->msId,
                provider: $request->provider,
                yearFrom: $request->yearFrom,
                description: $request->description,
                powerPs: $request->powerPs,
                powerKw: $request->powerKw,
                engineType: $request->engineType,
                allowChangeFields: $request->allowChangeFields,
                yearTo: $request->yearTo,
                descriptionShort: $request->descriptionShort,
                localizedName: $request->localizedName,
                gearType: $request->gearType,
                driveType: $request->driveType,
                brakeSystemType: $request->brakeSystemType,
                numberOfCylinders: $request->numberOfCylinders,
                capacityLt: $request->capacityLt,
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
                    links: $engineLinks,
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
                description: $modification->description,
                powerPs: $modification->powerPs,
                powerKw: $modification->powerKw,
                engineType: $modification->engineType,
                allowChangeFields: $modification->allowChangeFields,
                yearTo: $modification->yearTo,
                descriptionShort: $modification->descriptionShort,
                localizedName: $modification->localizedName,
                gearType: $modification->gearType,
                driveType: $modification->driveType,
                brakeSystemType: $modification->brakeSystemType,
                numberOfCylinders: $modification->numberOfCylinders,
                capacityLt: $modification->capacityLt,
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
     * Возвращает только существующие связи, перечисленные во входящей modification mutation.
     *
     * Шаги:
     * 1) Для каждого engine request требовать внешний `eng_id`.
     * 2) Найти существующий двигатель по `eng_id`.
     * 3) Вернуть `null`, если хотя бы один двигатель не найден.
     * 4) Сохранить provider=TD только для уже существующих TD-связей из БД.
     * 5) Назначить новым и OD-связям provider=OD, не доверяя внешнему payload.
     * 6) Вернуть список engine links для синхронизации pivot-связей.
     *
     * @param  list<ModificationEngineRequestDTO>  $requests
     * @param  array<int, int>  $currentTdEngineIds
     * @return list<ModificationEngineLinkDTO>|null
     */
    private function existingEngineLinks(array $requests, array $currentTdEngineIds): ?array
    {
        $links = [];

        foreach ($requests as $request) {
            $engine = $this->engines->findByEngId($request->engId);
            if ($engine === null) {
                return null;
            }

            $links[] = new ModificationEngineLinkDTO(
                engine: $engine,
                provider: in_array($request->engId, $currentTdEngineIds, true)
                    ? ProviderEnum::TD
                    : ProviderEnum::OD,
            );
        }

        return $links;
    }

    /**
     * Возвращает внешние eng_id TD-связей из входящего списка связей.
     *
     * Шаги:
     * 1) Оставить только связи с provider=TD.
     * 2) Вернуть список их внешних eng_id для проверки write policy.
     *
     * @param  list<ModificationEngineLinkDTO>  $links
     * @return array<int, int>
     */
    private function tdRelationEngineIds(array $links): array
    {
        return collect($links)
            ->filter(fn (ModificationEngineLinkDTO $link): bool => $link->provider === ProviderEnum::TD)
            ->map(fn (ModificationEngineLinkDTO $link): int => $link->engine->engId)
            ->values()
            ->all();
    }
}
