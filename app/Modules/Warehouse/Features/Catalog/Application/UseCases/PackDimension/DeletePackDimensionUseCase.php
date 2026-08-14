<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogCascadeDeleteServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\DeletePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Shared\Domain\Events\PackDimension\PackDimensionDeleted;
use Throwable;

/**
 * Выполняет удаление упаковочного размера Warehouse из внешнего сообщения.
 */
final readonly class DeletePackDimensionUseCase
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     *
     * Шаги:
     * 1) Принять repository поиска упаковки и cascade service комплектов.
     * 2) Принять command удаления, idempotency cache и result service.
     */
    public function __construct(
        private PackDimensionRepositoryInterface $packDimensions,
        private PackDimensionCommandInterface $command,
        private WarehouseCatalogCascadeDeleteServiceInterface $cascade,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Удаляет упаковочный размер вручную вместе со связанными наборами.
     *
     * Шаги:
     * 1) Зафиксировать operation_id в cache для защиты от повторов.
     * 2) Найти упаковочный размер и вернуть rejected result, если записи нет.
     * 3) Удалить связанные kits, затем упаковку, и отправить PackDimensionDeleted.
     * 4) Вернуть completed result или снять cache-флаг при техническом сбое.
     */
    public function execute(DeletePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);
        if (! $operationAccepted) {
            return null;
        }

        try {
            $packDimension = $this->packDimensions->findById($request->id);
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

            $this->cascade->deleteKitsByPackDimensionId($request->id);
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
