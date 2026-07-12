<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Modification;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\DeleteModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\DeleteModificationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Modification\ModificationDeleted;
use Throwable;

final readonly class DeleteModificationUseCase implements DeleteModificationUseCaseInterface
{
    public function __construct(
        private ModificationRepositoryInterface $modifications,
        private ModificationCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    public function execute(DeleteModificationRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $modification = $this->modifications->firstByModIdAndType($request->modId, $request->type->value);
            if ($modification === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Delete, $request->modId, CatalogMutationRejectReasonEnum::NotFound);
            }

            $engineModificationCount = $this->modifications->engineModificationCountByModIdAndType($request->modId, $request->type->value);
            if ($engineModificationCount === null) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Delete, $request->modId, CatalogMutationRejectReasonEnum::NotFound);
            }

            if ($engineModificationCount > 0) {
                return $this->results->rejected($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Delete, $request->modId, CatalogMutationRejectReasonEnum::DeleteBlocked, ['engine_modifications_count' => $engineModificationCount], $modification->id);
            }

            $this->command->deleteByModIdAndType($request->modId, $request->type->value);
            event(new ModificationDeleted($request->userId, $request->operationId, $request->modId, $request->type, (int) $modification->id));

            return $this->results->completed($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Delete, $request->modId, $modification->id);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed($request->userId, $request->operationId, CatalogEntityEnum::Modification, CatalogMutationOperationEnum::Delete, $request->modId);

            throw $e;
        }
    }
}
