<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Manufacturer;

use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\CreateManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\DeleteManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\StartManufacturerMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\UpdateManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\CreateManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\DeleteManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\ManufacturerMutationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\UpdateManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

final readonly class StartManufacturerMutationUseCase implements StartManufacturerMutationUseCaseInterface
{
    public function __construct(
        private CreateManufacturerUseCaseInterface $createManufacturer,
        private UpdateManufacturerUseCaseInterface $updateManufacturer,
        private DeleteManufacturerUseCaseInterface $deleteManufacturer,
    ) {}

    public function execute(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            CatalogMutationOperationEnum::Create => $this->create($request),
            CatalogMutationOperationEnum::Update => $this->update($request),
            CatalogMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    private function create(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createManufacturer->execute($createRequest);
    }

    private function update(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateManufacturer->execute($updateRequest);
    }

    private function delete(ManufacturerMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteManufacturer->execute($deleteRequest);
    }

    private function createRequest(ManufacturerMutationRequestDTO $request): CreateManufacturerRequestDTO
    {
        return $request->request;
    }

    private function updateRequest(ManufacturerMutationRequestDTO $request): UpdateManufacturerRequestDTO
    {
        return $request->request;
    }

    private function deleteRequest(ManufacturerMutationRequestDTO $request): DeleteManufacturerRequestDTO
    {
        return $request->request;
    }
}
