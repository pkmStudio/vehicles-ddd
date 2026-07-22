<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\CreateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use Throwable;

/**
 * Оркестрирует сценарий мутации автомобилей из внешнего сообщения.
 */
final readonly class CreateVehicleUseCase implements CreateVehicleUseCaseInterface
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
    public function execute(CreateVehicleRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            if ($this->vehicles->findByMsId($request->msId) !== null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Vehicle,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $request->msId,
                    reason: CatalogMutationRejectReasonEnum::AlreadyExists,
                );
            }

            $manufacturerId = $this->vehicles->manufacturerIdByMfaId($request->mfaId);
            if ($manufacturerId === null) {
                return $this->results->rejected(
                    userId: $request->userId,
                    operationId: $request->operationId,
                    entity: CatalogEntityEnum::Vehicle,
                    operation: CatalogMutationOperationEnum::Create,
                    externalId: $request->msId,
                    reason: CatalogMutationRejectReasonEnum::ManufacturerNotFound,
                );
            }

            $parentId = null;
            if ($request->parentMsId !== null) {
                $parentId = $this->vehicles->vehicleIdByMsId($request->parentMsId);
                if ($parentId === null) {
                    return $this->results->rejected(
                        userId: $request->userId,
                        operationId: $request->operationId,
                        entity: CatalogEntityEnum::Vehicle,
                        operation: CatalogMutationOperationEnum::Create,
                        externalId: $request->msId,
                        reason: CatalogMutationRejectReasonEnum::ParentVehicleNotFound,
                    );
                }
            }

            $vehicleData = new VehicleData(
                msId: $request->msId,
                mfaId: $request->mfaId,
                manufacturerId: $manufacturerId,
                name: $request->name,
                type: $request->type,
                steeringType: $request->steeringType,
                typeCarcase: $request->typeCarcase,
                provider: $request->provider,
                generation: $request->generation,
                generationYearFrom: $request->generationYearFrom,
                generationYearTo: $request->generationYearTo,
                parentId: $parentId,
                excelTableId: $request->excelTableId,
                localizedName: $request->localizedName,
                generationShort: $request->generationShort,
                isAllow: $request->isAllow,
            );

            $vehicle = $this->command->create($vehicleData);

            event(new VehicleCreated(
                userId: $request->userId,
                operationId: $request->operationId,
                vehicle: $vehicle->toArray(),
            ));

            return $this->results->completed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Vehicle,
                operation: CatalogMutationOperationEnum::Create,
                externalId: $vehicle->msId,
                recordId: $vehicle->id,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->results->failed(
                userId: $request->userId,
                operationId: $request->operationId,
                entity: CatalogEntityEnum::Vehicle,
                operation: CatalogMutationOperationEnum::Create,
                externalId: $request->msId,
            );

            throw $e;
        }
    }
}
