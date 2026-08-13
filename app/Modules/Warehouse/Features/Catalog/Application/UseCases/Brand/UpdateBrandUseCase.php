<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\UpdateBrandUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\UpdateBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Shared\Domain\Events\Brand\BrandUpdated;
use Throwable;

/**
 * Выполняет обновление Warehouse-бренда из внешнего сообщения.
 */
final readonly class UpdateBrandUseCase implements UpdateBrandUseCaseInterface
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     *
     * Шаги:
     * 1) Принять repository поиска бренда и проверки нового имени.
     * 2) Принять command записи, idempotency cache и result service.
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
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $existingBrand = $this->brands->findById($request->id);

            if ($existingBrand === null) {
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

            $sameNameBrand = $this->brands->findByName($request->name);
            if ($sameNameBrand !== null && $sameNameBrand->id !== $request->id) {
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
                brand: $brand->toArray(),
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
