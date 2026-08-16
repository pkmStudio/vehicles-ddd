<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\CreateKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\DeleteKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\UpdateKitRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Оркестрирует старт мутации Warehouse-набора из внешнего сообщения.
 */
final readonly class StartKitMutationUseCase
{
    /**
     * Инициализирует use case конкретных операций.
     * Шаги:
     * 1) Сохранить сценарий создания Warehouse-набора.
     * 2) Сохранить сценарий обновления Warehouse-набора.
     * 3) Сохранить сценарий удаления Warehouse-набора.
     */
    public function __construct(
        private CreateKitUseCase $createKit,
        private UpdateKitUseCase $updateKit,
        private DeleteKitUseCase $deleteKit,
    ) {}

    /**
     * Запускает сценарий мутации Warehouse-набора по типу операции.
     * Шаги:
     * 1) Прочитать operation из общего KitMutationRequestDTO.
     * 2) Для create извлечь CreateKitRequestDTO и вызвать create use case.
     * 3) Для update извлечь UpdateKitRequestDTO и вызвать update use case.
     * 4) Для delete извлечь DeleteKitRequestDTO и вызвать delete use case.
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
     * Шаги:
     * 1) Получить CreateKitRequestDTO из общего request.
     * 2) Передать DTO в CreateKitUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function create(KitMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createKit->execute($createRequest);
    }

    /**
     * Делегирует обновление набора профильному use case.
     * Шаги:
     * 1) Получить UpdateKitRequestDTO из общего request.
     * 2) Передать DTO в UpdateKitUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function update(KitMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateKit->execute($updateRequest);
    }

    /**
     * Делегирует удаление набора профильному use case.
     * Шаги:
     * 1) Получить DeleteKitRequestDTO из общего request.
     * 2) Передать DTO в DeleteKitUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function delete(KitMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteKit->execute($deleteRequest);
    }

    /**
     * Возвращает DTO создания набора из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function createRequest(KitMutationRequestDTO $request): CreateKitRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO обновления набора из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function updateRequest(KitMutationRequestDTO $request): UpdateKitRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO удаления набора из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function deleteRequest(KitMutationRequestDTO $request): DeleteKitRequestDTO
    {
        return $request->request;
    }
}
