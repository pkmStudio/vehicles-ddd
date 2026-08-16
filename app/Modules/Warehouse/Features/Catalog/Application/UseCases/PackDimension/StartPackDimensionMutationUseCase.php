<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\PackDimension;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\CreatePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\DeletePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\UpdatePackDimensionRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Оркестрирует старт мутации упаковочного размера Warehouse из внешнего сообщения.
 */
final readonly class StartPackDimensionMutationUseCase
{
    /**
     * Инициализирует use case конкретных операций.
     * Шаги:
     * 1) Сохранить сценарий создания упаковочного размера Warehouse.
     * 2) Сохранить сценарий обновления упаковочного размера Warehouse.
     * 3) Сохранить сценарий удаления упаковочного размера Warehouse.
     */
    public function __construct(
        private CreatePackDimensionUseCase $createPackDimension,
        private UpdatePackDimensionUseCase $updatePackDimension,
        private DeletePackDimensionUseCase $deletePackDimension,
    ) {}

    /**
     * Запускает сценарий мутации упаковочного размера Warehouse по типу операции.
     * Шаги:
     * 1) Прочитать operation из общего PackDimensionMutationRequestDTO.
     * 2) Для create извлечь CreatePackDimensionRequestDTO и вызвать create use case.
     * 3) Для update извлечь UpdatePackDimensionRequestDTO и вызвать update use case.
     * 4) Для delete извлечь DeletePackDimensionRequestDTO и вызвать delete use case.
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
     * Шаги:
     * 1) Получить CreatePackDimensionRequestDTO из общего request.
     * 2) Передать DTO в CreatePackDimensionUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function create(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createPackDimension->execute($createRequest);
    }

    /**
     * Делегирует обновление упаковочного размера профильному use case.
     * Шаги:
     * 1) Получить UpdatePackDimensionRequestDTO из общего request.
     * 2) Передать DTO в UpdatePackDimensionUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function update(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updatePackDimension->execute($updateRequest);
    }

    /**
     * Делегирует удаление упаковочного размера профильному use case.
     * Шаги:
     * 1) Получить DeletePackDimensionRequestDTO из общего request.
     * 2) Передать DTO в DeletePackDimensionUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function delete(PackDimensionMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deletePackDimension->execute($deleteRequest);
    }

    /**
     * Возвращает DTO создания упаковочного размера из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function createRequest(PackDimensionMutationRequestDTO $request): CreatePackDimensionRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO обновления упаковочного размера из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function updateRequest(PackDimensionMutationRequestDTO $request): UpdatePackDimensionRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO удаления упаковочного размера из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function deleteRequest(PackDimensionMutationRequestDTO $request): DeletePackDimensionRequestDTO
    {
        return $request->request;
    }
}
