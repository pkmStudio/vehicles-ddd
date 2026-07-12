<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Engine;

use App\Vehicles\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\CreateEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\CreateEngineRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Engine\EngineCreated;
use App\Vehicles\Catalog\Domain\ModelData\EngineData;
use Throwable;

final readonly class CreateEngineUseCase implements CreateEngineUseCaseInterface
{
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(CreateEngineRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->engines->firstByEngId($request->engId) !== null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Create, $request->engId, CatalogMutationRejectReasonEnum::AlreadyExists);
            }

            $engine = $this->command->create(new EngineData(
                engId: $request->engId,
                codeEngine: $request->codeEngine,
                engPowerKwStart: $request->engPowerKwStart,
                engPowerKwUpto: $request->engPowerKwUpto,
                engPowerPsStart: $request->engPowerPsStart,
                engPowerPsUpto: $request->engPowerPsUpto,
                engineCapacity: $request->engineCapacity,
                cylinderDiameter: $request->cylinderDiameter,
                cylinderCount: $request->cylinderCount,
                engNumberOfValves: $request->engNumberOfValves,
                engFuelType: $request->engFuelType,
                groupId: $request->groupId,
            ));

            event(new EngineCreated($request->userId, $request->operationId, $engine));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Create, $engine->engId, $engine->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Create, $request->engId);

            throw $e;
        }
    }
}
