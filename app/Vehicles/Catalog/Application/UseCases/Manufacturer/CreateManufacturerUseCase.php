<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Manufacturer;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\CreateManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\CreateManufacturerRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Manufacturer\ManufacturerCreated;
use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;
use Throwable;

final readonly class CreateManufacturerUseCase implements CreateManufacturerUseCaseInterface
{
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
        private ManufacturerCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(CreateManufacturerRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->manufacturers->firstByMfaId($request->mfaId) !== null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Create, $request->mfaId, CatalogMutationRejectReasonEnum::AlreadyExists);
            }

            $manufacturer = $this->command->create(new ManufacturerData(
                mfaId: $request->mfaId,
                name: $request->name,
                provider: $request->provider,
            ));

            event(new ManufacturerCreated($request->userId, $request->operationId, $manufacturer));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Create, $manufacturer->mfaId, $manufacturer->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Manufacturer, CatalogMutationOperationEnum::Create, $request->mfaId);

            throw $e;
        }
    }
}
