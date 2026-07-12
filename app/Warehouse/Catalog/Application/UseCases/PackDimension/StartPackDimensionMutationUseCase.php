<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\PackDimension;

use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\CreatePackDimensionUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\DeletePackDimensionUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\StartPackDimensionMutationUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\UpdatePackDimensionUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\PackDimension\CreatePackDimensionRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\PackDimension\DeletePackDimensionRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\PackDimension\UpdatePackDimensionRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Оркестрирует старт мутации упаковочного размера Warehouse из внешнего сообщения.
 */
final readonly class StartPackDimensionMutationUseCase implements StartPackDimensionMutationUseCaseInterface
{
    /**
     * Инициализирует use case конкретных операций.
     */
    public function __construct(
        private CreatePackDimensionUseCaseInterface $createPackDimension,
        private UpdatePackDimensionUseCaseInterface $updatePackDimension,
        private DeletePackDimensionUseCaseInterface $deletePackDimension,
    ) {}

    /**
     * Запускает сценарий мутации упаковочного размера Warehouse по типу операции.
     */
    public function execute(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        return match ($request->operation) {
            WarehouseCatalogMutationOperationEnum::Create => $this->create($request),
            WarehouseCatalogMutationOperationEnum::Update => $this->update($request),
            WarehouseCatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Делегирует создание упаковочного размера профильному use case.
     */
    private function create(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createPackDimension->execute($createRequest);
    }

    /**
     * Делегирует обновление упаковочного размера профильному use case.
     */
    private function update(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updatePackDimension->execute($updateRequest);
    }

    /**
     * Делегирует удаление упаковочного размера профильному use case.
     */
    private function delete(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deletePackDimension->execute($deleteRequest);
    }

    /**
     * Возвращает DTO создания упаковочного размера из общего DTO мутации.
     */
    private function createRequest(PackDimensionMutationRequestDTO $request): CreatePackDimensionRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO обновления упаковочного размера из общего DTO мутации.
     */
    private function updateRequest(PackDimensionMutationRequestDTO $request): UpdatePackDimensionRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO удаления упаковочного размера из общего DTO мутации.
     */
    private function deleteRequest(PackDimensionMutationRequestDTO $request): DeletePackDimensionRequestDTO
    {
        return $request->request;
    }
}
