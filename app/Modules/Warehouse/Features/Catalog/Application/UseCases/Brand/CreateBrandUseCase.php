<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\CreateBrandUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandLookupDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\CreateBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Shared\Domain\Events\Brand\BrandCreated;
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
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $existingBrand = $this->brands->find(BrandLookupDTO::byName($request->name));

            if ($existingBrand !== null) {
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
