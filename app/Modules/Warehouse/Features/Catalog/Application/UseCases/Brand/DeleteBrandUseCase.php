<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\DeleteBrandUseCaseInterface;
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
final readonly class DeleteBrandUseCase implements DeleteBrandUseCaseInterface
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     */
    public function __construct(
        private BrandRepositoryInterface $brands,
        private BrandCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Удаляет бренд вручную, если нет номенклатуры с этим brand_id.
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не выполнил удаление дважды.
     * 2) Проверить существование бренда и отсутствие связанных номенклатур.
     * 3) Удалить бренд через Command и отправить доменный факт.
     * 4) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(DeleteBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $brand = $this->brands->find($request->id);
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

            $blockers = $this->brands->deletionBlockers($request->id);
            if ($blockers === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Brand,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                );
            }

            if ($blockers->hasBlockers()) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Brand,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::DeleteBlocked,
                    errors: $blockers->toArray(),
                    recordId: $request->id,
                    businessKey: $brand->name,
                );
            }

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
