<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications\PackDimensionBulkDeleteNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogCascadeDeleteServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionBulkDeleteErrorDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionBulkDeleteRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionBulkDeleteResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Shared\Domain\Events\PackDimension\PackDimensionDeleted;
use Throwable;

/**
 * Выполняет массовое удаление упаковок с каскадом по наборам.
 */
final readonly class BulkDeletePackDimensionsUseCase
{
    /**
     * Получает порты чтения, записи, каскадного удаления, идемпотентности и публикации результата.
     */
    public function __construct(
        private PackDimensionRepositoryInterface $packDimensions,
        private PackDimensionCommandInterface $command,
        private WarehouseCatalogCascadeDeleteServiceInterface $cascade,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private PackDimensionBulkDeleteNotificationServiceInterface $notifier,
    ) {}

    /**
     * Удаляет существующие упаковки, отсутствующие id считает skipped и публикует один result event.
     */
    public function execute(PackDimensionBulkDeleteRequestDTO $request): ?PackDimensionBulkDeleteResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $ids = $this->uniqueIds($request->ids);
            $packDimensions = $this->packDimensions->findByIds($ids);
            $deleteIds = [];
            $errors = [];

            foreach ($ids as $id) {
                $packDimension = $packDimensions->get($id);
                if (! $packDimension instanceof PackDimensionData) {
                    $errors[] = $this->notFoundError($id);

                    continue;
                }

                $deleteIds[] = $id;
                $this->cascade->deleteKitsByPackDimensionId($id);
                event(new PackDimensionDeleted($request->userId, $request->operationId, $id, $packDimension->name));
            }

            $this->command->deleteByIds($deleteIds);
            $result = $this->result($request, count($deleteIds), count($errors), 0, $errors);
            $this->notifier->notify($result);

            return $result;
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->notifier->notify($this->failedResult($request));

            throw $e;
        }
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function uniqueIds(array $ids): array
    {
        return array_values(array_unique($ids));
    }

    /**
     * Собирает typed error для отсутствующей упаковки.
     */
    private function notFoundError(int $id): PackDimensionBulkDeleteErrorDTO
    {
        return new PackDimensionBulkDeleteErrorDTO($id, WarehouseCatalogMutationRejectReasonEnum::NotFound->value);
    }

    /**
     * Собирает DTO результата bulk-delete упаковок.
     *
     * @param  list<PackDimensionBulkDeleteErrorDTO>  $errors
     */
    private function result(
        PackDimensionBulkDeleteRequestDTO $request,
        int $deleted,
        int $skipped,
        int $failed,
        array $errors,
    ): PackDimensionBulkDeleteResultDTO {
        return new PackDimensionBulkDeleteResultDTO(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: WarehouseCatalogEntityEnum::PackDimension,
            status: $errors === [] ? WarehouseCatalogMutationStatusEnum::Completed : WarehouseCatalogMutationStatusEnum::CompletedWithErrors,
            requested: count($request->ids),
            deleted: $deleted,
            skipped: $skipped,
            failed: $failed,
            errors: $errors,
        );
    }

    /**
     * Собирает failed result для технической ошибки, сорвавшей всю операцию.
     */
    private function failedResult(PackDimensionBulkDeleteRequestDTO $request): PackDimensionBulkDeleteResultDTO
    {
        return $this->result($request, 0, 0, count($request->ids), [new PackDimensionBulkDeleteErrorDTO(null, 'technical_failure')]);
    }
}
