<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications\KitBulkDeleteNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitBulkDeleteErrorDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitBulkDeleteRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitBulkDeleteResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use App\Modules\Warehouse\Shared\Domain\Events\Kit\KitDeleted;
use Throwable;

/**
 * Выполняет массовое удаление Warehouse-наборов с единым result event.
 */
final readonly class BulkDeleteKitsUseCase
{
    /**
     * Получает порты чтения, записи, идемпотентности и публикации результата.
     */
    public function __construct(
        private KitRepositoryInterface $kits,
        private KitCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private KitBulkDeleteNotificationServiceInterface $notifier,
    ) {}

    /**
     * Удаляет существующие наборы, отсутствующие id считает skipped.
     *
     * Шаги:
     * 1) Принять operationId через idempotency cache.
     * 2) Найти существующие id и отделить отсутствующие строки.
     * 3) Удалить найденные наборы одним command-вызовом.
     * 4) Опубликовать один bulk result с counters и ошибками по skipped id.
     */
    public function execute(KitBulkDeleteRequestDTO $request): ?KitBulkDeleteResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);
        if (! $operationAccepted) {
            return null;
        }

        $ids = $this->uniqueIds($request->ids);

        try {
            $existingIds = $this->kits->existingIds($ids)->all();
            $missingIds = $this->missingIds($ids, $existingIds);

            $deleted = count($existingIds);
            if ($existingIds !== []) {
                $this->command->deleteByIds($existingIds);

                foreach ($existingIds as $kitId) {
                    event(new KitDeleted(
                        userId: $request->userId,
                        operationId: $request->operationId,
                        kitId: $kitId,
                    ));
                }
            }

            $result = new KitBulkDeleteResultDTO(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                status: $missingIds === []
                    ? WarehouseCatalogMutationStatusEnum::Completed
                    : WarehouseCatalogMutationStatusEnum::CompletedWithErrors,
                requested: count($request->ids),
                deleted: $deleted,
                skipped: count($missingIds),
                failed: 0,
                errors: $this->notFoundErrors($missingIds),
            );

            $this->notifier->notify($result);

            return $result;
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);

            $result = new KitBulkDeleteResultDTO(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Kit,
                status: WarehouseCatalogMutationStatusEnum::Failed,
                requested: count($request->ids),
                deleted: 0,
                skipped: 0,
                failed: count($request->ids),
                errors: [
                    new KitBulkDeleteErrorDTO(
                        id: null,
                        reason: 'technical_failure',
                    ),
                ],
            );

            $this->notifier->notify($result);

            throw $e;
        }
    }

    /**
     * Убирает дубли из входного списка id, сохраняя порядок первого появления.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function uniqueIds(array $ids): array
    {
        $seen = [];
        $result = [];

        foreach ($ids as $id) {
            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $result[] = $id;
        }

        return $result;
    }

    /**
     * Возвращает id, которые были запрошены, но не найдены в каталоге.
     *
     * @param  list<int>  $requestedIds
     * @param  array<int, int>  $existingIds
     * @return list<int>
     */
    private function missingIds(array $requestedIds, array $existingIds): array
    {
        $existing = array_fill_keys($existingIds, true);
        $missing = [];

        foreach ($requestedIds as $id) {
            if (! isset($existing[$id])) {
                $missing[] = $id;
            }
        }

        return $missing;
    }

    /**
     * Собирает typed errors для отсутствующих наборов.
     *
     * @param  list<int>  $ids
     * @return list<KitBulkDeleteErrorDTO>
     */
    private function notFoundErrors(array $ids): array
    {
        return array_map(
            static fn (int $id): KitBulkDeleteErrorDTO => new KitBulkDeleteErrorDTO(
                id: $id,
                reason: WarehouseCatalogMutationRejectReasonEnum::NotFound->value,
            ),
            $ids,
        );
    }
}
