<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\CreateNomenclatureUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\DeleteNomenclatureUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\StartNomenclatureMutationUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\UpdateNomenclatureUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\CreateNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\DeleteNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureMutationRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\UpdateNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;

/**
 * Оркестрирует старт мутации Warehouse-номенклатуры из внешнего сообщения.
 */
final readonly class StartNomenclatureMutationUseCase implements StartNomenclatureMutationUseCaseInterface
{
    /**
     * Инициализирует use case конкретных операций.
     */
    public function __construct(
        private CreateNomenclatureUseCaseInterface $createNomenclature,
        private UpdateNomenclatureUseCaseInterface $updateNomenclature,
        private DeleteNomenclatureUseCaseInterface $deleteNomenclature,
    ) {}

    /**
     * Запускает сценарий мутации Warehouse-номенклатуры по типу операции.
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
     */
    private function create(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createNomenclature->execute($createRequest);
    }

    /**
     * Делегирует обновление номенклатуры профильному use case.
     */
    private function update(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateNomenclature->execute($updateRequest);
    }

    /**
     * Делегирует удаление номенклатуры профильному use case.
     */
    private function delete(NomenclatureMutationRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteNomenclature->execute($deleteRequest);
    }

    /**
     * Возвращает DTO создания номенклатуры из общего DTO мутации.
     */
    private function createRequest(NomenclatureMutationRequestDTO $request): CreateNomenclatureRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO обновления номенклатуры из общего DTO мутации.
     */
    private function updateRequest(NomenclatureMutationRequestDTO $request): UpdateNomenclatureRequestDTO
    {
        return $request->request;
    }

    /**
     * Возвращает DTO удаления номенклатуры из общего DTO мутации.
     */
    private function deleteRequest(NomenclatureMutationRequestDTO $request): DeleteNomenclatureRequestDTO
    {
        return $request->request;
    }
}
