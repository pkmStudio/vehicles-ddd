<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogCascadeDeleteServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\DeleteBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Shared\Domain\Events\Brand\BrandDeleted;
use Throwable;

/**
 * Выполняет удаление Warehouse-бренда из внешнего сообщения.
 */
final readonly class DeleteBrandUseCase
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     *
     * Шаги:
     * 1) Принять repository поиска бренда и cascade service связанных данных.
     * 2) Принять command удаления, idempotency cache и result service.
     */
    public function __construct(
        private BrandRepositoryInterface $brands,
        private BrandCommandInterface $command,
        private WarehouseCatalogCascadeDeleteServiceInterface $cascade,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Удаляет бренд вручную вместе со связанными данными.
     *
     * Шаги:
     * 1) Зафиксировать operation_id в cache для защиты от повторов.
     * 2) Найти бренд и вернуть rejected result, если записи нет.
     * 3) Удалить связанные номенклатуры, затем бренд, и отправить BrandDeleted.
     * 4) Вернуть completed result или снять cache-флаг при техническом сбое.
     */
    public function execute(DeleteBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $brand = $this->brands->findById($request->id);
            if ($brand === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Brand,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                );
            }

            $this->cascade->deleteNomenclaturesByBrandId($request->id);
            $this->command->deleteById($request->id);

            event(new BrandDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                brandId: $request->id,
                name: $brand->name,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Brand,
                operation: WarehouseCatalogMutationOperationEnum::Delete,
                recordId: $request->id,
                businessKey: $brand->name,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Brand,
                operation: WarehouseCatalogMutationOperationEnum::Delete,
                recordId: $request->id,
            );

            throw $e;
        }
    }
}
