<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\UpdateEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\UpdateEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\Events\Engine\EngineUpdated;
use Throwable;

/**
 * Оркестрирует сценарий мутации двигателей из внешнего сообщения.
 */
final readonly class UpdateEngineUseCase implements UpdateEngineUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
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
                codeEngine: $request->codeEngine,
                engPowerKwStart: $request->engPowerKwStart,
                engPowerKwUpto: $request->engPowerKwUpto,
                engPowerPsStart: $request->engPowerPsStart,
                engPowerPsUpto: $request->engPowerPsUpto,
                engineCapacity: $request->engineCapacity,
                cylinderDiameter: $request->cylinderDiameter,
                cylinderCount: $request->cylinderCount,
                engNumberOfValves: $request->engNumberOfValves,
                engFuelType: $request->engFuelType,
                groupId: $request->groupId,
                id: $existing->id,
            );

            $engine = $this->command->update($engineData);

            event(new EngineUpdated(
                userId: $request->userId,
                operationId: $request->operationId,
                engine: $engine->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Engine,
                operation: CatalogMutationOperationEnum::Update,
                externalId: $engine->engId,
                recordId: $engine->id,
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
