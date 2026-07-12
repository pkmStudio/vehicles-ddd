<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Modification;

use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\CreateModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\DeleteModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\StartModificationMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\UpdateModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\DeleteModificationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\UpdateModificationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

final readonly class StartModificationMutationUseCase implements StartModificationMutationUseCaseInterface
{
    public function __construct(
        private CreateModificationUseCaseInterface $createModification,
        private UpdateModificationUseCaseInterface $updateModification,
        private DeleteModificationUseCaseInterface $deleteModification,
    ) {}

    public function execute(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            CatalogMutationOperationEnum::Create => $this->create($request),
            CatalogMutationOperationEnum::Update => $this->update($request),
            CatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    private function create(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createModification->execute($createRequest);
    }

    private function update(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateModification->execute($updateRequest);
    }

    private function delete(ModificationMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteModification->execute($deleteRequest);
    }

    private function createRequest(ModificationMutationRequestDTO $request): CreateModificationRequestDTO
    {
        return $request->request;
    }

    private function updateRequest(ModificationMutationRequestDTO $request): UpdateModificationRequestDTO
    {
        return $request->request;
    }

    private function deleteRequest(ModificationMutationRequestDTO $request): DeleteModificationRequestDTO
    {
        return $request->request;
    }
}
