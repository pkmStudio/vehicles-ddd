<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Modification;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\CreateModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Modification\ModificationCreated;
use App\Vehicles\Catalog\Domain\ModelData\ModificationData;
use Throwable;

final readonly class CreateModificationUseCase implements CreateModificationUseCaseInterface
{
    public function __construct(
        private ModificationRepositoryInterface $modifications,
        private VehicleRepositoryInterface $vehicles,
        private ModificationCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(CreateModificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->modifications->firstByModIdAndType($request->modId, $request->type->value) !== null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Create, $request->modId, CatalogMutationRejectReasonEnum::AlreadyExists);
            }

            $vehicleId = $this->vehicles->vehicleIdByMsId($request->msId);
            if ($vehicleId === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Create, $request->modId, CatalogMutationRejectReasonEnum::VehicleNotFound);
            }

            $modification = $this->command->create(new ModificationData(
                modId: $request->modId,
                type: $request->type,
                vehicleId: $vehicleId,
                msId: $request->msId,
                yearFrom: $request->yearFrom,
                yearTo: $request->yearTo,
                description: $request->description,
                powerPs: $request->powerPs,
                powerKw: $request->powerKw,
                engineType: $request->engineType,
                gearType: $request->gearType,
                driveType: $request->driveType,
                brakeSystemType: $request->brakeSystemType,
                numberOfCylinders: $request->numberOfCylinders,
                capacityLt: $request->capacityLt,
            ));

            event(new ModificationCreated($request->userId, $request->operationId, $modification));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Create, $modification->modId, $modification->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Create, $request->modId);

            throw $e;
        }
    }
}
