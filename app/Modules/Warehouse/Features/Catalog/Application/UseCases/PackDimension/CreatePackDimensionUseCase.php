<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\PackDimension\CreatePackDimensionUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\CreatePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Shared\Domain\Events\PackDimension\PackDimensionCreated;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;
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
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не создал дубль.
     * 2) Проверить существование type для упаковочного размера.
     * 3) Собрать PackDimensionData, записать упаковку через Command и отправить доменный факт.
     * 4) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(CreatePackDimensionRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $type = $this->types->findById($request->typeId);

            if ($type === null) {
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
                packDimension: $packDimension->toArray(),
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
