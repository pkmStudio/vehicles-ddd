<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Notifications\EngineBulkDeleteNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogCascadeDeleteServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineBulkDeleteErrorDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineBulkDeleteRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineBulkDeleteResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Engine\EngineDeleted;
use Throwable;

/**
 * Выполняет массовое удаление двигателей с единым result event.
 */
final readonly class BulkDeleteEnginesUseCase
{
    /**
     * Получает порты чтения, записи, каскадного удаления, идемпотентности и публикации результата.
     */
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
        private CatalogCascadeDeleteServiceInterface $cascade,
        private CatalogMutationCacheServiceInterface $cache,
        private EngineBulkDeleteNotificationServiceInterface $notifier,
    ) {}

    /**
     * Удаляет OD-двигатели по eng_id, TD и отсутствующие строки возвращает в counters/errors.
     */
    public function execute(EngineBulkDeleteRequestDTO $request): ?EngineBulkDeleteResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $engIds = $this->uniqueIds($request->engIds);
            $engines = $this->engines->findByEngIds($engIds);
            $deleted = 0;
            $skipped = 0;
            $failed = 0;
            $errors = [];

            foreach ($engIds as $engId) {
                $engine = $engines->get($engId);
                if (! $engine instanceof EngineData) {
                    $skipped++;
                    $errors[] = new EngineBulkDeleteErrorDTO(null, CatalogMutationRejectReasonEnum::NotFound->value, 'eng_id:'.$engId);

                    continue;
                }

                if ($engine->provider === ProviderEnum::TD) {
                    $failed++;
                    $errors[] = new EngineBulkDeleteErrorDTO($engine->id, CatalogMutationRejectReasonEnum::ProviderDeleteForbidden->value, 'eng_id:'.$engId);

                    continue;
                }

                $this->cascade->deleteEngineDependencies((int) $engine->id);
                $this->command->deleteByEngId($engId);
                event(new EngineDeleted($request->userId, $request->operationId, $engId, (int) $engine->id));
                $deleted++;
            }

            $result = $this->result($request, $deleted, $skipped, $failed, $errors);
            $this->notifier->notify($result);

            return $result;
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->notifier->notify($this->failedResult($request));

            throw $e;
        }
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function uniqueIds(array $ids): array
    {
        return array_values(array_unique($ids));
    }

    /**
     * Собирает DTO результата bulk-delete двигателей.
     *
     * @param  list<EngineBulkDeleteErrorDTO>  $errors
     */
    private function result(
        EngineBulkDeleteRequestDTO $request,
        int $deleted,
        int $skipped,
        int $failed,
        array $errors,
    ): EngineBulkDeleteResultDTO {
        return new EngineBulkDeleteResultDTO(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Engine,
            status: $errors === [] ? CatalogMutationStatusEnum::Completed : CatalogMutationStatusEnum::CompletedWithErrors,
            requested: count($request->engIds),
            deleted: $deleted,
            skipped: $skipped,
            failed: $failed,
            errors: $errors,
        );
    }

    /**
     * Собирает failed result для технической ошибки, сорвавшей всю операцию.
     */
    private function failedResult(EngineBulkDeleteRequestDTO $request): EngineBulkDeleteResultDTO
    {
        return $this->result($request, 0, 0, count($request->engIds), [new EngineBulkDeleteErrorDTO(null, 'technical_failure')]);
    }
}
