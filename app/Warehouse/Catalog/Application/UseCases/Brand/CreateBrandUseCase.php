<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\Brand;

use App\Warehouse\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\CreateBrandUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\Brand\CreateBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Shared\Domain\Events\Brand\BrandCreated;
use App\Warehouse\Catalog\Domain\ModelData\BrandData;
use Throwable;

/**
 * Выполняет создание Warehouse-бренда из внешнего сообщения.
 */
final readonly class CreateBrandUseCase implements CreateBrandUseCaseInterface
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
     * Создаёт бренд, если имя ещё не занято.
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не создал дубль.
     * 2) Проверить уникальность имени и вернуть rejected-результат при конфликте.
     * 3) Собрать BrandData, записать бренд через Command и отправить доменный факт.
     * 4) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(CreateBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->brands->firstByName($request->name) !== null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Brand,
                    operation: WarehouseCatalogMutationOperationEnum::Create,
                    reason: WarehouseCatalogMutationRejectReasonEnum::AlreadyExists,
                    businessKey: $request->name,
                );
            }

            $data = new BrandData(
                name: $request->name,
                numberSert: $request->numberSert,
                dateStart: $request->dateStart,
                dateEnd: $request->dateEnd,
                char: $request->char,
            );

            $brand = $this->command->create($data);

            event(new BrandCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                brand: $brand->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Brand,
                operation: WarehouseCatalogMutationOperationEnum::Create,
                recordId: $brand->id,
                businessKey: $brand->name,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Brand,
                operation: WarehouseCatalogMutationOperationEnum::Create,
                businessKey: $request->name,
            );

            throw $e;
        }
    }
}
