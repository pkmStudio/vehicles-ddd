<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications\BrandBulkDeleteNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogCascadeDeleteServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandBulkDeleteErrorDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandBulkDeleteRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandBulkDeleteResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Shared\Domain\Events\Brand\BrandDeleted;
use Throwable;

/**
 * Выполняет массовое удаление складских брендов с каскадом по номенклатурам.
 */
final readonly class BulkDeleteBrandsUseCase
{
    /**
     * Получает порты чтения, записи, каскадного удаления, идемпотентности и публикации результата.
     */
    public function __construct(
        private BrandRepositoryInterface $brands,
        private BrandCommandInterface $command,
        private WarehouseCatalogCascadeDeleteServiceInterface $cascade,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private BrandBulkDeleteNotificationServiceInterface $notifier,
    ) {}

    /**
     * Удаляет существующие бренды, отсутствующие id считает skipped и публикует один result event.
     */
    public function execute(BrandBulkDeleteRequestDTO $request): ?BrandBulkDeleteResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $ids = $this->uniqueIds($request->ids);
            $brands = $this->brands->findByIds($ids);
            $deleteIds = [];
            $errors = [];

            foreach ($ids as $id) {
                $brand = $brands->get($id);
                if (! $brand instanceof BrandData) {
                    $errors[] = $this->notFoundError($id);

                    continue;
                }

                $deleteIds[] = $id;
                $this->cascade->deleteNomenclaturesByBrandId($id);
                event(new BrandDeleted($request->userId, $request->operationId, $id, $brand->name));
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
     * Собирает typed error для отсутствующего бренда.
     */
    private function notFoundError(int $id): BrandBulkDeleteErrorDTO
    {
        return new BrandBulkDeleteErrorDTO(
            id: $id,
            reason: WarehouseCatalogMutationRejectReasonEnum::NotFound->value,
        );
    }

    /**
     * Собирает DTO результата bulk-delete брендов.
     *
     * @param  list<BrandBulkDeleteErrorDTO>  $errors
     */
    private function result(
        BrandBulkDeleteRequestDTO $request,
        int $deleted,
        int $skipped,
        int $failed,
        array $errors,
    ): BrandBulkDeleteResultDTO {
        return new BrandBulkDeleteResultDTO(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: WarehouseCatalogEntityEnum::Brand,
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
    private function failedResult(BrandBulkDeleteRequestDTO $request): BrandBulkDeleteResultDTO
    {
        return $this->result(
            request: $request,
            deleted: 0,
            skipped: 0,
            failed: count($request->ids),
            errors: [new BrandBulkDeleteErrorDTO(null, 'technical_failure')],
        );
    }
}
