<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\CreatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\DeletePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\StartPartSpecificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\UpdatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\CreatePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\DeletePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\UpdatePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Оркестрирует сценарий мутации спецификаций деталей из внешнего сообщения.
 */
final readonly class StartPartSpecificationMutationUseCase implements StartPartSpecificationMutationUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private CreatePartSpecificationUseCaseInterface $createPartSpecification,
        private UpdatePartSpecificationUseCaseInterface $updatePartSpecification,
        private DeletePartSpecificationUseCaseInterface $deletePartSpecification,
    ) {}

    /**
     * Запускает сценарий мутации спецификаций деталей по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            CatalogMutationOperationEnum::Create => $this->create($request),
            CatalogMutationOperationEnum::Update => $this->update($request),
            CatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     */
    private function create(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createPartSpecification->execute($createRequest);
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     */
    private function update(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updatePartSpecification->execute($updateRequest);
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     */
    private function delete(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deletePartSpecification->execute($deleteRequest);
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     */
    private function createRequest(PartSpecificationMutationRequestDTO $request): CreatePartSpecificationRequestDTO
    {
        return $request->request;
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     */
    private function updateRequest(PartSpecificationMutationRequestDTO $request): UpdatePartSpecificationRequestDTO
    {
        return $request->request;
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     */
    private function deleteRequest(PartSpecificationMutationRequestDTO $request): DeletePartSpecificationRequestDTO
    {
        return $request->request;
    }
}
