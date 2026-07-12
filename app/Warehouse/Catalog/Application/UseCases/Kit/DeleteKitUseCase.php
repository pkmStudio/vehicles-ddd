<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\Kit;

use App\Warehouse\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit\DeleteKitUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\Kit\DeleteKitRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Catalog\Domain\Events\Kit\KitDeleted;
use Throwable;

/**
 * Выполняет удаление Warehouse-набора из внешнего сообщения.
 */
final readonly class DeleteKitUseCase implements DeleteKitUseCaseInterface
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     */
    public function __construct(
        private KitRepositoryInterface $kits,
        private KitCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Удаляет набор и вручную очищает pivot kit_nomenclature внутри Command.
     */
    public function execute(DeleteKitRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $kit = $this->kits->find($request->id);
            if ($kit === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Kit,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                );
            }

            $this->command->deleteById($request->id);

            event(new KitDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                kitId: $request->id,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Delete,
                recordId: $request->id,
                businessKey: $kit->importHash,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                operation: WarehouseCatalogMutationOperationEnum::Delete,
                recordId: $request->id,
            );

            throw $e;
        }
    }
}
