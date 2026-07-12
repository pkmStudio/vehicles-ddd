<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\Brand;

use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\CreateBrandUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\DeleteBrandUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\StartBrandMutationUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\UpdateBrandUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\Brand\BrandMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Brand\CreateBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Brand\DeleteBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\Brand\UpdateBrandRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Оркестрирует старт мутации Warehouse-бренда из внешнего сообщения.
 */
final readonly class StartBrandMutationUseCase implements StartBrandMutationUseCaseInterface
{
    /**
     * Инициализирует use case конкретных операций.
     */
    public function __construct(
        private CreateBrandUseCaseInterface $createBrand,
        private UpdateBrandUseCaseInterface $updateBrand,
        private DeleteBrandUseCaseInterface $deleteBrand,
    ) {}

    /**
     * Запускает сценарий мутации Warehouse-бренда по типу операции.
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
     */
    private function create(BrandMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createBrand->execute($createRequest);
    }

    /**
     * Делегирует обновление бренда профильному use case.
     */
    private function update(BrandMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateBrand->execute($updateRequest);
    }

    /**
     * Делегирует удаление бренда профильному use case.
     */
    private function delete(BrandMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteBrand->execute($deleteRequest);
    }

    /**
     * Возвращает DTO создания бренда из общего DTO мутации.
     */
    private function createRequest(BrandMutationRequestDTO $request): CreateBrandRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO обновления бренда из общего DTO мутации.
     */
    private function updateRequest(BrandMutationRequestDTO $request): UpdateBrandRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO удаления бренда из общего DTO мутации.
     */
    private function deleteRequest(BrandMutationRequestDTO $request): DeleteBrandRequestDTO
    {
        return $request->request;
    }
}
