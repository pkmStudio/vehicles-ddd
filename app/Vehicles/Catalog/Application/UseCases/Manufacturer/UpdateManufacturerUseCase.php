<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Manufacturer;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\UpdateManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\UpdateManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Manufacturer\ManufacturerUpdated;
use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;
use Throwable;

final readonly class UpdateManufacturerUseCase implements UpdateManufacturerUseCaseInterface
{
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
        private ManufacturerCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(UpdateManufacturerRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $existing = $this->manufacturers->firstByMfaId($request->mfaId);
            if ($existing === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Update, $request->mfaId, CatalogMutationRejectReasonEnum::NotFound);
            }

            $manufacturer = $this->command->update(new ManufacturerData(
                mfaId: $request->mfaId,
                name: $request->name,
                provider: $request->provider,
                id: $existing->id,
            ));

            event(new ManufacturerUpdated($request->userId, $request->operationId, $manufacturer));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Update, $manufacturer->mfaId, $manufacturer->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Update, $request->mfaId);

            throw $e;
        }
    }
}
