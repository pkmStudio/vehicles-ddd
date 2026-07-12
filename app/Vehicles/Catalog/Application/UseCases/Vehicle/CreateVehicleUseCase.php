<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\CreateVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\VehicleCreated;
use App\Vehicles\Catalog\Domain\ModelData\VehicleData;
use Throwable;

final readonly class CreateVehicleUseCase implements CreateVehicleUseCaseInterface
{
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(CreateVehicleRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->vehicles->firstByMsId($request->msId) !== null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Create, $request->msId, CatalogMutationRejectReasonEnum::AlreadyExists);
            }

            $manufacturerId = $this->vehicles->manufacturerIdByMfaId($request->mfaId);
            if ($manufacturerId === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Create, $request->msId, CatalogMutationRejectReasonEnum::ManufacturerNotFound);
            }

            $parentId = null;
            if ($request->parentMsId !== null) {
                $parentId = $this->vehicles->vehicleIdByMsId($request->parentMsId);
                if ($parentId === null) {
                    return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Create, $request->msId, CatalogMutationRejectReasonEnum::ParentVehicleNotFound);
                }
            }

            $vehicle = $this->command->create(new VehicleData(
                msId: $request->msId,
                mfaId: $request->mfaId,
                manufacturerId: $manufacturerId,
                name: $request->name,
                type: $request->type,
                steeringType: $request->steeringType,
                typeCarcase: $request->typeCarcase,
                provider: $request->provider,
                generation: $request->generation,
                generationYearFrom: $request->generationYearFrom,
                generationYearTo: $request->generationYearTo,
                parentId: $parentId,
                excelTableId: $request->excelTableId,
                localizedName: $request->localizedName,
                generationShort: $request->generationShort,
                isAllow: $request->isAllow,
            ));

            event(new VehicleCreated($request->userId, $request->operationId, $vehicle));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Create, $vehicle->msId, $vehicle->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Create, $request->msId);

            throw $e;
        }
    }
}
