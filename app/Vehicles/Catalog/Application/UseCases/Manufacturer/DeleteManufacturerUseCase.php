<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Manufacturer;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\DeleteManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\DeleteManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Manufacturer\ManufacturerDeleted;
use Throwable;

final readonly class DeleteManufacturerUseCase implements DeleteManufacturerUseCaseInterface
{
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
        private ManufacturerCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(DeleteManufacturerRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $manufacturer = $this->manufacturers->firstByMfaId($request->mfaId);
            if ($manufacturer === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Delete, $request->mfaId, CatalogMutationRejectReasonEnum::NotFound);
            }

            $vehicleCount = $this->manufacturers->vehicleCountByMfaId($request->mfaId);
            if ($vehicleCount === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Delete, $request->mfaId, CatalogMutationRejectReasonEnum::NotFound);
            }

            if ($vehicleCount > 0) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Delete, $request->mfaId, CatalogMutationRejectReasonEnum::DeleteBlocked, ['vehicles_count' => $vehicleCount], $manufacturer->id);
            }

            $this->command->deleteByMfaId($request->mfaId);
            event(new ManufacturerDeleted($request->userId, $request->operationId, $request->mfaId, (int) $manufacturer->id));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Delete, $request->mfaId, $manufacturer->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Delete, $request->mfaId);

            throw $e;
        }
    }
}
