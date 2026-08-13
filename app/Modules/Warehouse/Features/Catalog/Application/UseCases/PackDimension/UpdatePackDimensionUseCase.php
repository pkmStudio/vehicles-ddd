<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension\UpdatePackDimensionUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\UpdatePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Shared\Domain\Events\PackDimension\PackDimensionUpdated;
use Throwable;

/**
 * Выполняет обновление упаковочного размера Warehouse из внешнего сообщения.
 */
final readonly class UpdatePackDimensionUseCase implements UpdatePackDimensionUseCaseInterface
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     *
     * Шаги:
     * 1) Принять repositories упаковки и типов для проверок update.
     * 2) Принять command записи, idempotency cache и result service.
     */
    public function __construct(
        private PackDimensionRepositoryInterface $packDimensions,
        private TypeRepositoryInterface $types,
        private PackDimensionCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Обновляет упаковочный размер, если запись и тип существуют.
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не выполнил обновление дважды.
     * 2) Проверить существование упаковочного размера и type.
     * 3) Собрать PackDimensionData с identity, обновить запись через Command и отправить доменный факт.
     * 4) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(UpdatePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $existingPackDimension = $this->packDimensions->findById($request->id);

            if ($existingPackDimension === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::PackDimension,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                    businessKey: $request->name,
                );
            }

            $type = $this->types->findById($request->typeId);

            if ($type === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::PackDimension,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::TypeNotFound,
                    recordId: $request->id,
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
                id: $request->id,
            );

            $packDimension = $this->command->update($data);

            event(new PackDimensionUpdated(
                userId: $request->userId,
                operationId: $request->operationId,
                packDimension: $packDimension->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::PackDimension,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                recordId: $packDimension->id,
                businessKey: $packDimension->name,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::PackDimension,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                recordId: $request->id,
                businessKey: $request->name,
            );

            throw $e;
        }
    }
}
