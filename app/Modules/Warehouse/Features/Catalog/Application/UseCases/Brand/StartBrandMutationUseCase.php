<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\CreateBrandUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\DeleteBrandUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\StartBrandMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\UpdateBrandUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\CreateBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\DeleteBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\UpdateBrandRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Оркестрирует старт мутации Warehouse-бренда из внешнего сообщения.
 */
final readonly class StartBrandMutationUseCase implements StartBrandMutationUseCaseInterface
{
    /**
     * Инициализирует use case конкретных операций.
     * Шаги:
     * 1) Сохранить сценарий создания Warehouse-бренда.
     * 2) Сохранить сценарий обновления Warehouse-бренда.
     * 3) Сохранить сценарий удаления Warehouse-бренда.
     */
    public function __construct(
        private CreateBrandUseCaseInterface $createBrand,
        private UpdateBrandUseCaseInterface $updateBrand,
        private DeleteBrandUseCaseInterface $deleteBrand,
    ) {}

    /**
     * Запускает сценарий мутации Warehouse-бренда по типу операции.
     * Шаги:
     * 1) Прочитать operation из общего BrandMutationRequestDTO.
     * 2) Для create извлечь CreateBrandRequestDTO и вызвать create use case.
     * 3) Для update извлечь UpdateBrandRequestDTO и вызвать update use case.
     * 4) Для delete извлечь DeleteBrandRequestDTO и вызвать delete use case.
     */
    public function execute(BrandMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        return match ($request->operation) {
            WarehouseCatalogMutationOperationEnum::Create => $this->create($request),
            WarehouseCatalogMutationOperationEnum::Update => $this->update($request),
            WarehouseCatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Делегирует создание бренда профильному use case.
     * Шаги:
     * 1) Получить CreateBrandRequestDTO из общего request.
     * 2) Передать DTO в CreateBrandUseCaseInterface.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function create(BrandMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createBrand->execute($createRequest);
    }

    /**
     * Делегирует обновление бренда профильному use case.
     * Шаги:
     * 1) Получить UpdateBrandRequestDTO из общего request.
     * 2) Передать DTO в UpdateBrandUseCaseInterface.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function update(BrandMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateBrand->execute($updateRequest);
    }

    /**
     * Делегирует удаление бренда профильному use case.
     * Шаги:
     * 1) Получить DeleteBrandRequestDTO из общего request.
     * 2) Передать DTO в DeleteBrandUseCaseInterface.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function delete(BrandMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteBrand->execute($deleteRequest);
    }

    /**
     * Возвращает DTO создания бренда из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function createRequest(BrandMutationRequestDTO $request): CreateBrandRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO обновления бренда из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function updateRequest(BrandMutationRequestDTO $request): UpdateBrandRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO удаления бренда из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function deleteRequest(BrandMutationRequestDTO $request): DeleteBrandRequestDTO
    {
        return $request->request;
    }
}
