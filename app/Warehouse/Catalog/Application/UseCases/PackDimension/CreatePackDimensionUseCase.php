<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\PackDimension;

use App\Warehouse\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\CreatePackDimensionUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\PackDimension\CreatePackDimensionRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Catalog\Domain\Events\PackDimension\PackDimensionCreated;
use App\Warehouse\Catalog\Domain\ModelData\PackDimensionData;
use Throwable;

/**
 * Выполняет создание упаковочного размера Warehouse из внешнего сообщения.
 */
final readonly class CreatePackDimensionUseCase implements CreatePackDimensionUseCaseInterface
{
    /**
     * Инициализирует чтение типов, запись, cache и result-сервис.
     */
    public function __construct(
        private TypeRepositoryInterface $types,
        private PackDimensionCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Создаёт упаковочный размер, если тип существует.
     */
    public function execute(CreatePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->types->find($request->typeId) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::PackDimension,
                    operation: WarehouseCatalogMutationOperationEnum::Create,
                    reason: WarehouseCatalogMutationRejectReasonEnum::TypeNotFound,
                    businessKey: $request->name,
                );
            }

            $data = new PackDimensionData(
                name: $request->name,
                weight: $request->weight,
                width: $request->width,
                height: $request->height,
                length: $request->length,
                price: $request->price,
                typeId: $request->typeId,
                generated: $request->generated,
            );

            $packDimension = $this->command->create($data);

            event(new PackDimensionCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                packDimension: $packDimension,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::PackDimension,
                operation: WarehouseCatalogMutationOperationEnum::Create,
                recordId: $packDimension->id,
                businessKey: $packDimension->name,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::PackDimension,
                operation: WarehouseCatalogMutationOperationEnum::Create,
                businessKey: $request->name,
            );

            throw $e;
        }
    }
}
