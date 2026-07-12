<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Engine;

use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\CreateEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\DeleteEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\StartEngineMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\UpdateEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\CreateEngineRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\DeleteEngineRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\EngineMutationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\UpdateEngineRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

final readonly class StartEngineMutationUseCase implements StartEngineMutationUseCaseInterface
{
    public function __construct(
        private CreateEngineUseCaseInterface $createEngine,
        private UpdateEngineUseCaseInterface $updateEngine,
        private DeleteEngineUseCaseInterface $deleteEngine,
    ) {}

    public function execute(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            CatalogMutationOperationEnum::Create => $this->create($request),
            CatalogMutationOperationEnum::Update => $this->update($request),
            CatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    private function create(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createEngine->execute($createRequest);
    }

    private function update(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateEngine->execute($updateRequest);
    }

    private function delete(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteEngine->execute($deleteRequest);
    }

    private function createRequest(EngineMutationRequestDTO $request): CreateEngineRequestDTO
    {
        return $request->request;
    }

    private function updateRequest(EngineMutationRequestDTO $request): UpdateEngineRequestDTO
    {
        return $request->request;
    }

    private function deleteRequest(EngineMutationRequestDTO $request): DeleteEngineRequestDTO
    {
        return $request->request;
    }
}
