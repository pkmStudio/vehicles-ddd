<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Application\UseCases\Nomenclature;

use App\Warehouse\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature\DeleteNomenclatureUseCaseInterface;
use App\Warehouse\Catalog\Domain\DTOs\Nomenclature\DeleteNomenclatureRequestDTO;
use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted;
use Throwable;

/**
 * Выполняет удаление Warehouse-номенклатуры из внешнего сообщения.
 */
final readonly class DeleteNomenclatureUseCase implements DeleteNomenclatureUseCaseInterface
{
    /**
     * Инициализирует чтение, запись, cache и result-сервис.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private NomenclatureCommandInterface $command,
        private WarehouseCatalogMutationCacheServiceInterface $cache,
        private WarehouseCatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Удаляет номенклатуру вручную, если нет kit_nomenclature и integrations.
     *
     * Шаги:
     * 1) Принять operationId через cache, чтобы повтор брокера не выполнил удаление дважды.
     * 2) Проверить существование записи и отсутствие блокирующих связей.
     * 3) Удалить номенклатуру через Command и отправить доменный факт.
     * 4) Вернуть completed-результат; на технической ошибке снять cache-флаг и пробросить исключение.
     */
    public function execute(DeleteNomenclatureRequestDTO $request): ?WarehouseCatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $nomenclature = $this->nomenclatures->find($request->id);
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

            $blockers = $this->nomenclatures->deletionBlockers($request->id);
            if ($blockers === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::NotFound,
                    recordId: $request->id,
                );
            }

            if ($blockers->hasBlockers()) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: WarehouseCatalogEntityEnum::Nomenclature,
                    operation: WarehouseCatalogMutationOperationEnum::Delete,
                    reason: WarehouseCatalogMutationRejectReasonEnum::DeleteBlocked,
                    errors: $blockers->toArray(),
                    recordId: $request->id,
                    businessKey: $nomenclature->partNumber,
                );
            }

            $this->command->deleteById($request->id);

            event(new NomenclatureDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                nomenclatureId: $request->id,
                partNumber: $nomenclature->partNumber,
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
