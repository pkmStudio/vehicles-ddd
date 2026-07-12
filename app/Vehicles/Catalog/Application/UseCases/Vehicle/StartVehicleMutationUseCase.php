<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\CreateVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\DeleteVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\UpdateVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\UpdateVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\VehicleMutationOperationEnum;

final readonly class StartVehicleMutationUseCase implements StartVehicleMutationUseCaseInterface
{
    public function __construct(
        private CreateVehicleUseCaseInterface $createVehicle,
        private UpdateVehicleUseCaseInterface $updateVehicle,
        private DeleteVehicleUseCaseInterface $deleteVehicle,
    ) {}

    public function execute(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        return match ($request->operation) {
            VehicleMutationOperationEnum::Create => $this->create($request),
            VehicleMutationOperationEnum::Update => $this->update($request),
            VehicleMutationOperationEnum::Delete => $this->delete($request),
        };
    }

    private function create(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $createRequest = $this->createRequest($request);

        return $this->createVehicle->execute($createRequest);
    }

    private function update(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $updateRequest = $this->updateRequest($request);

        return $this->updateVehicle->execute($updateRequest);
    }

    private function delete(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO
    {
        $deleteRequest = $this->deleteRequest($request);

        return $this->deleteVehicle->execute($deleteRequest);
    }

    private function createRequest(VehicleMutationRequestDTO $request): CreateVehicleRequestDTO
    {
        return $request->request;
    }

    private function updateRequest(VehicleMutationRequestDTO $request): UpdateVehicleRequestDTO
    {
        return $request->request;
    }

    private function deleteRequest(VehicleMutationRequestDTO $request): DeleteVehicleRequestDTO
    {
        return $request->request;
    }
}
