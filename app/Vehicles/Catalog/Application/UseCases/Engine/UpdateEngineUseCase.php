<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Engine;

use App\Vehicles\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\UpdateEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\UpdateEngineRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Engine\EngineUpdated;
use App\Vehicles\Catalog\Domain\ModelData\EngineData;
use Throwable;

final readonly class UpdateEngineUseCase implements UpdateEngineUseCaseInterface
{
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(UpdateEngineRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $existing = $this->engines->firstByEngId($request->engId);
            if ($existing === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Update, $request->engId, CatalogMutationRejectReasonEnum::NotFound);
            }

            $engine = $this->command->update(new EngineData(
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
                id: $existing->id,
            ));

            event(new EngineUpdated($request->userId, $request->operationId, $engine));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Update, $engine->engId, $engine->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Update, $request->engId);

            throw $e;
        }
    }
}
