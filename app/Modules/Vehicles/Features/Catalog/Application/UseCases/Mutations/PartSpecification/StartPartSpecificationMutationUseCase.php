<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\CreatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\DeletePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\StartPartSpecificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\UpdatePartSpecificationUseCaseInterface;
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
     * Получает use cases для трех поддерживаемых операций part specification mutation.
     *
     * Шаги:
     * 1) Принять create-сценарий спецификации.
     * 2) Принять update-сценарий спецификации.
     * 3) Принять delete-сценарий спецификации.
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
     * Делегирует create branch профильному part specification use case.
     *
     * Шаги:
     * 1) Извлечь create request из общего mutation DTO.
     * 2) Передать request в create use case и вернуть mutation result.
     */
    private function create(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createPartSpecification->execute($createRequest);
    }

    /**
     * Делегирует update branch профильному part specification use case.
     *
     * Шаги:
     * 1) Извлечь update request из общего mutation DTO.
     * 2) Передать request в update use case и вернуть mutation result.
     */
    private function update(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updatePartSpecification->execute($updateRequest);
    }

    /**
     * Делегирует delete branch профильному part specification use case.
     *
     * Шаги:
     * 1) Извлечь delete request из общего mutation DTO.
     * 2) Передать request в delete use case и вернуть mutation result.
     */
    private function delete(PartSpecificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deletePartSpecification->execute($deleteRequest);
    }

    /**
     * Извлекает create request из общего DTO part specification mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как CreatePartSpecificationRequestDTO для create use case.
     */
    private function createRequest(PartSpecificationMutationRequestDTO $request): CreatePartSpecificationRequestDTO
    {
        return $request->request;
    }

    /**
     * Извлекает update request из общего DTO part specification mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как UpdatePartSpecificationRequestDTO для update use case.
     */
    private function updateRequest(PartSpecificationMutationRequestDTO $request): UpdatePartSpecificationRequestDTO
    {
        return $request->request;
    }

    /**
     * Извлекает delete request из общего DTO part specification mutation.
     *
     * Шаги:
     * 1) Прочитать typed request, уже собранный boundary factory.
     * 2) Вернуть его как DeletePartSpecificationRequestDTO для delete use case.
     */
    private function deleteRequest(PartSpecificationMutationRequestDTO $request): DeletePartSpecificationRequestDTO
    {
        return $request->request;
    }
}
