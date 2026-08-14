<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogCascadeDeleteServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\DeleteEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Engine\EngineDeleted;
use Throwable;

/**
 * Оркестрирует сценарий мутации двигателей из внешнего сообщения.
 */
final readonly class DeleteEngineUseCase
{
    /**
     * Получает порты delete engine workflow.
     *
     * Шаги:
     * 1) Принять repository для поиска двигателя по eng_id.
     * 2) Принять cascade service для удаления engine dependencies перед engine delete.
     * 3) Принять command/cache/result сервисы для записи, идемпотентности и result event.
     */
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
        private CatalogCascadeDeleteServiceInterface $cascade,
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
    public function execute(DeleteEngineRequestDTO $request): ?CatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);
        if (! $operationAccepted) {
            return null;
        }

        try {
            $engine = $this->engines->findByEngId($request->engId);
            if ($engine === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Engine,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->engId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            $this->cascade->deleteEngineDependencies((int) $engine->id);
            $this->command->deleteByEngId($request->engId);
            event(new EngineDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                engId: $request->engId,
                engineId: (int) $engine->id,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Engine,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->engId,
                recordId: $engine->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Engine,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->engId,
            );

            throw $e;
        }
    }
}
