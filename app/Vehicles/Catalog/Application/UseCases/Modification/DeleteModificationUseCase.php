<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Modification;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\DeleteModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\DeleteModificationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Modification\ModificationDeleted;
use Throwable;

/**
 * Оркестрирует сценарий мутации модификаций из внешнего сообщения.
 */
final readonly class DeleteModificationUseCase implements DeleteModificationUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private ModificationRepositoryInterface $modifications,
        private ModificationCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
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
    public function execute(DeleteModificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $modification = $this->modifications->firstByModIdAndType(
                modId: $request->modId,
                type: $request->type->value,
            );
            if ($modification === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->modId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            $engineModificationCount = $this->modifications->engineModificationCountByModIdAndType(
                modId: $request->modId,
                type: $request->type->value,
            );
            if ($engineModificationCount === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->modId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            if ($engineModificationCount > 0) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Modification,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->modId,
                    reason: CatalogMutationRejectReasonEnum::DeleteBlocked,
                    errors: ['engine_modifications_count' => $engineModificationCount],
                    recordId: $modification->id,
                );
            }

            $this->command->deleteByModIdAndType(
                modId: $request->modId,
                type: $request->type->value,
            );
            event(new ModificationDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                modId: $request->modId,
                type: $request->type,
                modificationId: (int) $modification->id,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Modification,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->modId,
                recordId: $modification->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Modification,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->modId,
            );

            throw $e;
        }
    }
}
