<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\DeleteVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Events\Vehicle\VehicleDeleted;
use Throwable;

/**
 * Оркестрирует сценарий мутации автомобилей из внешнего сообщения.
 */
final readonly class DeleteVehicleUseCase implements DeleteVehicleUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private VehicleCommandInterface $command,
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
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $vehicle = $this->vehicles->firstByMsId($request->msId);
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

            $blockers = $this->vehicles->deletionBlockersByMsId($request->msId);
            if ($blockers === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Vehicle,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->msId,
                    reason: CatalogMutationRejectReasonEnum::NotFound,
                );
            }

            if ($blockers->hasBlockers()) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Vehicle,
                    operation: CatalogMutationOperationEnum::Delete,
                    externalId: $request->msId,
                    reason: CatalogMutationRejectReasonEnum::DeleteBlocked,
                    errors: $blockers->toArray(),
                    recordId: $vehicle->id,
                );
            }

            $this->command->deleteByMsId($request->msId);
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
