<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\PackDimension;

use App\Warehouse\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\DeletePackDimensionUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\PackDimension\DeletePackDimensionRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Catalog\Domain\Events\PackDimension\PackDimensionDeleted;
use Throwable;

/**
 * Выполняет удаление упаковочного размера Warehouse из внешнего сообщения.
 */
final readonly class DeletePackDimensionUseCase implements DeletePackDimensionUseCaseInterface
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     */
    public function __construct(
        private PackDimensionRepositoryInterface $packDimensions,
        private PackDimensionCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Удаляет упаковочный размер вручную, если его не используют наборы.
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не выполнил удаление дважды.
     * 2) Проверить существование упаковочного размера и отсутствие наборов, которые на него ссылаются.
     * 3) Удалить упаковочный размер через Command и отправить доменный факт.
     * 4) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(DeletePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $packDimension = $this->packDimensions->find($request->id);
            if ($packDimension === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::PackDimension,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                );
            }

            $blockers = $this->packDimensions->deletionBlockers($request->id);
            if ($blockers === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::PackDimension,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                );
            }

            if ($blockers->hasBlockers()) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::PackDimension,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::DeleteBlocked,
                    errors: $blockers->toArray(),
                    recordId: $request->id,
                    businessKey: $packDimension->name,
                );
            }

            $this->command->deleteById($request->id);

            event(new PackDimensionDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                packDimensionId: $request->id,
                name: $packDimension->name,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::PackDimension,
                operation: WarehouseCatalogMutationOperationEnum::Delete,
                recordId: $request->id,
                businessKey: $packDimension->name,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::PackDimension,
                operation: WarehouseCatalogMutationOperationEnum::Delete,
                recordId: $request->id,
            );

            throw $e;
        }
    }
}
