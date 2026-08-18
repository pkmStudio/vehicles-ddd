<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Mutations;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications\NomenclatureBulkDeleteNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureBulkDeleteErrorDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureBulkDeleteRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureBulkDeleteResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted;
use Throwable;

/**
 * Выполняет массовое удаление складской номенклатуры с единым result event.
 */
final readonly class BulkDeleteNomenclaturesUseCase
{
    /**
     * Получает порты чтения, записи, идемпотентности и публикации результата.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private NomenclatureCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private NomenclatureBulkDeleteNotificationServiceInterface $notifier,
    ) {}

    /**
     * Удаляет найденную номенклатуру, отсутствующие id считает skipped.
     */
    public function execute(NomenclatureBulkDeleteRequestDTO $request): ?NomenclatureBulkDeleteResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $ids = $this->uniqueIds($request->ids);
            $nomenclatures = $this->nomenclatures->findByIds($ids);
            $deleteIds = [];
            $errors = [];

            foreach ($ids as $id) {
                $nomenclature = $nomenclatures->get($id);
                if (! $nomenclature instanceof NomenclatureData) {
                    $errors[] = $this->notFoundError($id);

                    continue;
                }

                $deleteIds[] = $id;
                $integrationContexts = $this->nomenclatures->deletionIntegrationContexts($id)->values()->all();
                event(new NomenclatureDeleted(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    nomenclatureId: $id,
                    partNumber: $nomenclature->partNumber,
                    integrations: $integrationContexts,
                ));
            }

            $this->command->deleteByIds($deleteIds);
            $result = $this->result($request, count($deleteIds), count($errors), 0, $errors);
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
     * Собирает typed error для отсутствующей номенклатуры.
     */
    private function notFoundError(int $id): NomenclatureBulkDeleteErrorDTO
    {
        return new NomenclatureBulkDeleteErrorDTO($id, WarehouseCatalogMutationRejectReasonEnum::NotFound->value);
    }

    /**
     * Собирает DTO результата bulk-delete номенклатуры.
     *
     * @param  list<NomenclatureBulkDeleteErrorDTO>  $errors
     */
    private function result(
        NomenclatureBulkDeleteRequestDTO $request,
        int $deleted,
        int $skipped,
        int $failed,
        array $errors,
    ): NomenclatureBulkDeleteResultDTO {
        return new NomenclatureBulkDeleteResultDTO(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: WarehouseCatalogEntityEnum::Nomenclature,
            status: $errors === [] ? WarehouseCatalogMutationStatusEnum::Completed : WarehouseCatalogMutationStatusEnum::CompletedWithErrors,
            requested: count($request->ids),
            deleted: $deleted,
            skipped: $skipped,
            failed: $failed,
            errors: $errors,
        );
    }

    /**
     * Собирает failed result для технической ошибки, сорвавшей всю операцию.
     */
    private function failedResult(NomenclatureBulkDeleteRequestDTO $request): NomenclatureBulkDeleteResultDTO
    {
        return $this->result($request, 0, 0, count($request->ids), [new NomenclatureBulkDeleteErrorDTO(null, 'technical_failure')]);
    }
}
