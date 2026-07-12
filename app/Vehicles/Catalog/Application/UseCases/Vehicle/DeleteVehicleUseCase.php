<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\DeleteVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\VehicleDeleted;
use Throwable;

final readonly class DeleteVehicleUseCase implements DeleteVehicleUseCaseInterface
{
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(DeleteVehicleRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $vehicle = $this->vehicles->firstByMsId($request->msId);
            if ($vehicle === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Delete, $request->msId, CatalogMutationRejectReasonEnum::NotFound);
            }

            $blockers = $this->vehicles->deletionBlockersByMsId($request->msId);
            if ($blockers === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Delete, $request->msId, CatalogMutationRejectReasonEnum::NotFound);
            }

            if ($blockers->hasBlockers()) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Delete, $request->msId, CatalogMutationRejectReasonEnum::DeleteBlocked, $blockers->toArray(), $vehicle->id);
            }

            $this->command->deleteByMsId($request->msId);
            event(new VehicleDeleted($request->userId, $request->operationId, $request->msId, (int) $vehicle->id));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Delete, $request->msId, $vehicle->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Vehicle, CatalogMutationOperationEnum::Delete, $request->msId);

            throw $e;
        }
    }
}
