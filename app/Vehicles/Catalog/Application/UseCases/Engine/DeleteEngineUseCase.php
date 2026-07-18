<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Engine;

use App\Vehicles\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\DeleteEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\DeleteEngineRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Shared\Domain\Events\Engine\EngineDeleted;
use Throwable;

/**
 * Оркестрирует сценарий мутации двигателей из внешнего сообщения.
 */
final readonly class DeleteEngineUseCase implements DeleteEngineUseCaseInterface
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
    public function execute(DeleteEngineRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $engine = $this->engines->firstByEngId($request->engId);
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

            $blockers = $this->engines->deletionBlockersByEngId($request->engId);
            if ($blockers === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Engine,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->engId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            if (($blockers['engine_modifications_count'] > 0) || ($blockers['part_specifications_count'] > 0)) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Engine,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->engId,
                    reason: CatalogMutationRejectReasonEnum::DeleteBlocked,
                    errors: $blockers,
                    recordId: $engine->id,
                );
            }

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
