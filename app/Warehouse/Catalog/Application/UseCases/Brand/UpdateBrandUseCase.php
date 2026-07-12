<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\Brand;

use App\Warehouse\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\UpdateBrandUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\Brand\UpdateBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Catalog\Domain\Events\Brand\BrandUpdated;
use App\Warehouse\Catalog\Domain\ModelData\BrandData;
use Throwable;

/**
 * Выполняет обновление Warehouse-бренда из внешнего сообщения.
 */
final readonly class UpdateBrandUseCase implements UpdateBrandUseCaseInterface
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
     * Обновляет бренд, если запись существует и новое имя не занято другой записью.
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не выполнил обновление дважды.
     * 2) Проверить существование бренда и уникальность нового имени.
     * 3) Собрать BrandData с identity, обновить запись через Command и отправить доменный факт.
     * 4) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(UpdateBrandRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->brands->find($request->id) === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Brand,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                    businessKey: $request->name,
                );
            }

            if ($this->brands->nameExistsForAnother($request->name, $request->id)) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Brand,
                    operation: WarehouseCatalogMutationOperationEnum::Update,
                    reason: WarehouseCatalogMutationRejectReasonEnum::AlreadyExists,
                    recordId: $request->id,
                    businessKey: $request->name,
                );
            }

            $data = new BrandData(
                name: $request->name,
                numberSert: $request->numberSert,
                dateStart: $request->dateStart,
                dateEnd: $request->dateEnd,
                char: $request->char,
                id: $request->id,
            );

            $brand = $this->command->update($data);

            event(new BrandUpdated(
                userId: $request->userId,
                operationId: $request->operationId,
                brand: $brand,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Brand,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                recordId: $brand->id,
                businessKey: $brand->name,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Brand,
                operation: WarehouseCatalogMutationOperationEnum::Update,
                recordId: $request->id,
                businessKey: $request->name,
            );

            throw $e;
        }
    }
}
