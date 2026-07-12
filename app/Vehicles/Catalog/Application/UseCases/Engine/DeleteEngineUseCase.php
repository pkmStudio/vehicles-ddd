<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Engine;

use App\Vehicles\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\DeleteEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\DeleteEngineRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Engine\EngineDeleted;
use Throwable;

final readonly class DeleteEngineUseCase implements DeleteEngineUseCaseInterface
{
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(DeleteEngineRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $engine = $this->engines->firstByEngId($request->engId);
            if ($engine === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Delete, $request->engId, CatalogMutationRejectReasonEnum::NotFound);
            }

            $blockers = $this->engines->deletionBlockersByEngId($request->engId);
            if ($blockers === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Delete, $request->engId, CatalogMutationRejectReasonEnum::NotFound);
            }

            if (($blockers['engine_modifications_count'] > 0) || ($blockers['part_specifications_count'] > 0)) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Delete, $request->engId, CatalogMutationRejectReasonEnum::DeleteBlocked, $blockers, $engine->id);
            }

            $this->command->deleteByEngId($request->engId);
            event(new EngineDeleted($request->userId, $request->operationId, $request->engId, (int) $engine->id));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Delete, $request->engId, $engine->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Engine, CatalogMutationOperationEnum::Delete, $request->engId);

            throw $e;
        }
    }
}
