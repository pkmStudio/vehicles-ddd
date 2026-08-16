<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogCascadeDeleteServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleDeleted;
use Throwable;

/**
 * Оркестрирует сценарий мутации автомобилей из внешнего сообщения.
 */
final readonly class DeleteVehicleUseCase
{
    /**
     * Получает порты delete vehicle workflow.
     *
     * Шаги:
     * 1) Принять repository для поиска автомобиля по ms_id.
     * 2) Принять cascade service для удаления vehicle subtree dependencies.
     * 3) Принять cache/result сервисы для идемпотентности и публикации результата.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private CatalogCascadeDeleteServiceInterface $cascade,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
    ) {}

    /**
     * Выполняет сценарий мутации автомобилей.
     *
     * Шаги:
     * 1) Зафиксировать operationId для идемпотентности.
     * 2) Проверить бизнес-ограничения операции.
     * 3) Выполнить запись через Command и отправить доменное событие.
     * 4) Опубликовать унифицированный результат мутации.
     */
    public function execute(DeleteVehicleRequestDTO $request): ?CatalogMutationResultDTO
    {
        $operationAccepted = $this->cache->accept($request->operationId);
        if (! $operationAccepted) {
            return null;
        }

        try {
            $vehicle = $this->vehicles->findByMsId($request->msId);
            if ($vehicle === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Vehicle,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->msId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            if ($vehicle->provider === ProviderEnum::TD) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Vehicle,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->msId,
                    reason: CatalogMutationRejectReasonEnum::ProviderDeleteForbidden,
                    recordId: $vehicle->id,
                );
            }

            $this->cascade->deleteVehiclesByIds([(int) $vehicle->id]);
            event(new VehicleDeleted(
                userId: $request->userId,
                operationId: $request->operationId,
                msId: $request->msId,
                vehicleId: (int) $vehicle->id,
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Vehicle,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->msId,
                recordId: $vehicle->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Vehicle,
                operation: CatalogMutationOperationEnum::Delete,
                externalId: $request->msId,
            );

            throw $e;
        }
    }
}
