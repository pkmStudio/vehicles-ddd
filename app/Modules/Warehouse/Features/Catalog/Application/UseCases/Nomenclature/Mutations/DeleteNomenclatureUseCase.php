<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Mutations;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Mutations\DeleteNomenclatureUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\DeleteNomenclatureRequestDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted;
use Throwable;

/**
 * Выполняет удаление Warehouse-номенклатуры из внешнего сообщения.
 */
final readonly class DeleteNomenclatureUseCase implements DeleteNomenclatureUseCaseInterface
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     *
     * Шаги:
     * 1) Принять repository поиска номенклатуры и command удаления.
     * 2) Принять idempotency cache и result service для outbound ответа.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private NomenclatureCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Удаляет номенклатуру вручную вместе со связанными данными.
     *
     * Шаги:
     * 1) Зафиксировать operation_id в cache для защиты от повторов.
     * 2) Найти номенклатуру и вернуть rejected result, если записи нет.
     * 3) Удалить запись через command и отправить NomenclatureDeleted.
     * 4) Вернуть completed result или снять cache-флаг при техническом сбое.
     */
    public function execute(DeleteNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);

        if (! $operationAccepted) {
            return null;
        }

        try {
            $nomenclature = $this->nomenclatures->findById($request->id);
            if ($nomenclature === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                );
            }

            $toContextArray = fn ($context): array => $context->toArray();

            $integrationContexts = $this->nomenclatures
                ->deletionIntegrationContexts($request->id)
                ->map($toContextArray)
                ->values()
                ->all();

            $this->command->deleteById($request->id);

            event(new NomenclatureDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                nomenclatureId: $request->id,
                partNumber: $nomenclature->partNumber,
                integrations: $integrationContexts,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Nomenclature,
                operation: WarehouseCatalogMutationOperationEnum::Delete,
                recordId: $request->id,
                businessKey: $nomenclature->partNumber,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: WarehouseCatalogEntityEnum::Nomenclature,
                operation: WarehouseCatalogMutationOperationEnum::Delete,
                recordId: $request->id,
            );

            throw $e;
        }
    }
}
