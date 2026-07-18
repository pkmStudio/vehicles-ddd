<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\CreateKitUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\DeleteKitUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\StartKitMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Kit\UpdateKitUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\CreateKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\DeleteKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\UpdateKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Оркестрирует старт мутации Warehouse-набора из внешнего сообщения.
 */
final readonly class StartKitMutationUseCase implements StartKitMutationUseCaseInterface
{
    /**
     * Инициализирует use case конкретных операций.
     */
    public function __construct(
        private CreateKitUseCaseInterface $createKit,
        private UpdateKitUseCaseInterface $updateKit,
        private DeleteKitUseCaseInterface $deleteKit,
    ) {}

    /**
     * Запускает сценарий мутации Warehouse-набора по типу операции.
     */
    public function execute(KitMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        return match ($request->operation) {
            WarehouseCatalogMutationOperationEnum::Create => $this->create($request),
            WarehouseCatalogMutationOperationEnum::Update => $this->update($request),
            WarehouseCatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Делегирует создание набора профильному use case.
     */
    private function create(KitMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createKit->execute($createRequest);
    }

    /**
     * Делегирует обновление набора профильному use case.
     */
    private function update(KitMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateKit->execute($updateRequest);
    }

    /**
     * Делегирует удаление набора профильному use case.
     */
    private function delete(KitMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteKit->execute($deleteRequest);
    }

    /**
     * Возвращает DTO создания набора из общего DTO мутации.
     */
    private function createRequest(KitMutationRequestDTO $request): CreateKitRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO обновления набора из общего DTO мутации.
     */
    private function updateRequest(KitMutationRequestDTO $request): UpdateKitRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO удаления набора из общего DTO мутации.
     */
    private function deleteRequest(KitMutationRequestDTO $request): DeleteKitRequestDTO
    {
        return $request->request;
    }
}
