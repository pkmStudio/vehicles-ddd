<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\UpdateEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\EngineEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\EngineWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Engine\EngineUpdated;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\EngineWritePolicy;
use Throwable;

/**
 * Оркестрирует сценарий мутации двигателей из внешнего сообщения.
 */
final readonly class UpdateEngineUseCase
{
    /**
     * Получает порты update engine workflow.
     *
     * Шаги:
     * 1) Принять repository для проверки существования двигателя по eng_id.
     * 2) Принять command для сохранения обновленного EngineData.
     * 3) Принять cache/result сервисы для идемпотентности и публикации результата.
     */
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
        private EngineWritePolicy $writePolicy,
    ) {}

    /**
     * Выполняет сценарий мутации двигателей.
     *
     * Шаги:
     * 1) Зафиксировать operationId для идемпотентности.
     * 2) Проверить бизнес-ограничения операции.
     * 3) Выполнить запись через Command и отправить доменное событие.
     * 4) Опубликовать унифицированный результат мутации.
     */
    public function execute(UpdateEngineRequestDTO $request): ?CatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $existing = $this->engines->findByEngId($request->engId);
            if ($existing === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Engine,
                    operation: CatalogMutationOperationEnum::Update,
                    externalId: $request->engId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            $engineData = new EngineData(
                engId: $request->engId,
                provider: $request->provider,
                codeEngine: $request->codeEngine,
                powerKwStart: $request->powerKwStart,
                powerPsStart: $request->powerPsStart,
                fuelType: $request->fuelType,
                allowChangeFields: $request->allowChangeFields,
                powerKwUpto: $request->powerKwUpto,
                powerPsUpto: $request->powerPsUpto,
                engineCapacity: $request->engineCapacity,
                cylinderDiameter: $request->cylinderDiameter,
                cylinderCount: $request->cylinderCount,
                numberOfValves: $request->numberOfValves,
                groupId: $request->groupId,
                id: $existing->id,
            );

            $writeResult = $this->writePolicy->apply(
                incoming: EngineWritePolicyResultDTO::fromArray($engineData->toArray()),
                existing: EngineWritePolicyResultDTO::fromArray($existing->toArray()),
                sourceProvider: $request->provider,
            );

            $engine = $this->command->update(EngineData::from($writeResult->toArray()));

            $payload = new EngineEventPayloadDTO(
                id: (int) $engine->id,
                engId: $engine->engId,
                provider: $engine->provider,
                codeEngine: $engine->codeEngine,
                powerKwStart: $engine->powerKwStart,
                powerPsStart: $engine->powerPsStart,
                fuelType: $engine->fuelType,
                allowChangeFields: $engine->allowChangeFields,
                powerKwUpto: $engine->powerKwUpto,
                powerPsUpto: $engine->powerPsUpto,
                engineCapacity: $engine->engineCapacity,
                cylinderDiameter: $engine->cylinderDiameter,
                cylinderCount: $engine->cylinderCount,
                numberOfValves: $engine->numberOfValves,
                groupId: $engine->groupId,
            );

            event(new EngineUpdated(
                userId: $request->userId,
                operationId: $request->operationId,
                engine: $payload,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Engine,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $engine->engId,
                recordId: $engine->id,
            );
        } catch (ProviderOwnershipException $e) {
            return $this->results->rejected(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Engine,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $request->engId,
                reason: CatalogMutationRejectReasonEnum::ProviderOwnershipConflict,
                errors: $e->errors(),
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Engine,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $request->engId,
            );

            throw $e;
        }
    }
}
