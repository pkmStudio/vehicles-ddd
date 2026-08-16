<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Mutations;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\CreateNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\DeleteNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\UpdateNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Оркестрирует старт мутации Warehouse-номенклатуры из внешнего сообщения.
 */
final readonly class StartNomenclatureMutationUseCase
{
    /**
     * Инициализирует use case конкретных операций.
     * Шаги:
     * 1) Сохранить сценарий создания Warehouse-номенклатуры.
     * 2) Сохранить сценарий обновления Warehouse-номенклатуры.
     * 3) Сохранить сценарий удаления Warehouse-номенклатуры.
     */
    public function __construct(
        private CreateNomenclatureUseCase $createNomenclature,
        private UpdateNomenclatureUseCase $updateNomenclature,
        private DeleteNomenclatureUseCase $deleteNomenclature,
    ) {}

    /**
     * Запускает сценарий мутации Warehouse-номенклатуры по типу операции.
     * Шаги:
     * 1) Прочитать operation из общего NomenclatureMutationRequestDTO.
     * 2) Для create извлечь CreateNomenclatureRequestDTO и вызвать create use case.
     * 3) Для update извлечь UpdateNomenclatureRequestDTO и вызвать update use case.
     * 4) Для delete извлечь DeleteNomenclatureRequestDTO и вызвать delete use case.
     */
    public function execute(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        return match ($request->operation) {
            WarehouseCatalogMutationOperationEnum::Create => $this->create($request),
            WarehouseCatalogMutationOperationEnum::Update => $this->update($request),
            WarehouseCatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Делегирует создание номенклатуры профильному use case.
     * Шаги:
     * 1) Получить CreateNomenclatureRequestDTO из общего request.
     * 2) Передать DTO в CreateNomenclatureUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function create(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createNomenclature->execute($createRequest);
    }

    /**
     * Делегирует обновление номенклатуры профильному use case.
     * Шаги:
     * 1) Получить UpdateNomenclatureRequestDTO из общего request.
     * 2) Передать DTO в UpdateNomenclatureUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function update(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateNomenclature->execute($updateRequest);
    }

    /**
     * Делегирует удаление номенклатуры профильному use case.
     * Шаги:
     * 1) Получить DeleteNomenclatureRequestDTO из общего request.
     * 2) Передать DTO в DeleteNomenclatureUseCase.
     * 3) Вернуть DTO результата или null без дополнительной обработки.
     */
    private function delete(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteNomenclature->execute($deleteRequest);
    }

    /**
     * Возвращает DTO создания номенклатуры из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function createRequest(NomenclatureMutationRequestDTO $request): CreateNomenclatureRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO обновления номенклатуры из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function updateRequest(NomenclatureMutationRequestDTO $request): UpdateNomenclatureRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO удаления номенклатуры из общего DTO мутации.
     * Шаги:
     * 1) Вернуть типизированный request, уже подготовленный границу validator/фабрику.
     */
    private function deleteRequest(NomenclatureMutationRequestDTO $request): DeleteNomenclatureRequestDTO
    {
        return $request->request;
    }
}
