<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\Vehicle\VehicleMutationWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\UpdateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\UpdateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationWriteContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;
use Throwable;

/**
 * Оркестрирует сценарий мутации автомобилей из внешнего сообщения.
 */
final readonly class UpdateVehicleUseCase implements UpdateVehicleUseCaseInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
        private VehicleCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
        private VehicleMutationWritePolicyInterface $writePolicy,
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
    public function execute(UpdateVehicleRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        try {
            $existing = $this->resolveExisting($request);
            if ($existing instanceof CatalogMutationResultDTO) {
                return $existing;
            }

            $manufacturerId = $this->resolveManufacturerId($request, $existing);
            if ($manufacturerId instanceof CatalogMutationResultDTO) {
                return $manufacturerId;
            }

            $parentId = $this->resolveParentId($request, $existing);
            if ($parentId instanceof CatalogMutationResultDTO) {
                return $parentId;
            }

            $vehicleData = $this->prepareVehicleData(
                request: $request,
                existing: $existing,
                manufacturerId: $manufacturerId,
                parentId: $parentId,
            );

            $vehicle = $this->command->update($vehicleData);
            $this->publishUpdatedEvent($request, $vehicle);

            return $this->completed($request, $vehicle);
        } catch (Throwable $e) {
            $this->failed($request);

            throw $e;
        }
    }

    /**
     * Находит существующий автомобиль или возвращает rejected result.
     */
    private function resolveExisting(UpdateVehicleRequestDTO $request): VehicleData|CatalogMutationResultDTO
    {
        $existing = $this->vehicles->findByMsId($request->msId);
        if ($existing !== null) {
            return $existing;
        }

        return $this->rejected(
            request: $request,
            reason: CatalogMutationRejectReasonEnum::NotFound,
        );
    }

    /**
     * Возвращает manufacturer id для записи, учитывая locked поля текущего provider.
     */
    private function resolveManufacturerId(
        UpdateVehicleRequestDTO $request,
        VehicleData $existing,
    ): int|CatalogMutationResultDTO {
        if (! $this->writePolicy->allowsCatalogManagedFields($existing)) {
            return $existing->manufacturerId;
        }

        $manufacturer = $this->resolveManufacturer($request);
        if ($manufacturer instanceof CatalogMutationResultDTO) {
            return $manufacturer;
        }

        return (int) $manufacturer->id;
    }

    /**
     * Находит производителя для OD-managed обновления или возвращает rejected result.
     */
    private function resolveManufacturer(UpdateVehicleRequestDTO $request): ManufacturerData|CatalogMutationResultDTO
    {
        $manufacturer = $this->manufacturers->findByMfaId($request->mfaId);
        if ($manufacturer !== null) {
            return $manufacturer;
        }

        return $this->rejected(
            request: $request,
            reason: CatalogMutationRejectReasonEnum::ManufacturerNotFound,
        );
    }

    /**
     * Возвращает parent id для записи, учитывая locked поля текущего provider.
     */
    private function resolveParentId(
        UpdateVehicleRequestDTO $request,
        VehicleData $existing,
    ): int|CatalogMutationResultDTO|null {
        if (! $this->writePolicy->allowsCatalogManagedFields($existing)) {
            return $existing->parentId;
        }

        if ($request->parentMsId === null) {
            return null;
        }

        $parent = $this->vehicles->findByMsId($request->parentMsId);
        if ($parent !== null) {
            return $parent->id;
        }

        return $this->rejected(
            request: $request,
            reason: CatalogMutationRejectReasonEnum::ParentVehicleNotFound,
        );
    }

    /**
     * Собирает incoming VehicleData и применяет catalog update policy перед записью.
     */
    private function prepareVehicleData(
        UpdateVehicleRequestDTO $request,
        VehicleData $existing,
        int $manufacturerId,
        ?int $parentId,
    ): VehicleData {
        $incomingData = new VehicleData(
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
            id: $existing->id,
        );

        return $this->writePolicy->applyForUpdate(
            incoming: $incomingData,
            existing: $existing,
            context: new VehicleMutationWriteContextDTO(
                operationId: $request->operationId,
            ),
        );
    }

    /**
     * Публикует факт обновления автомобиля.
     */
    private function publishUpdatedEvent(
        UpdateVehicleRequestDTO $request,
        VehicleData $vehicle,
    ): void {
        event(new VehicleUpdated(
            userId: $request->userId,
            operationId: $request->operationId,
            vehicle: $vehicle->toArray(),
        ));
    }

    /**
     * Собирает completed result для update-сценария.
     */
    private function completed(
        UpdateVehicleRequestDTO $request,
        VehicleData $vehicle,
    ): CatalogMutationResultDTO {
        return $this->results->completed(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Vehicle,
            operation: CatalogMutationOperationEnum::Update,
            externalId: $vehicle->msId,
            recordId: $vehicle->id,
        );
    }

    /**
     * Собирает rejected result для update-сценария.
     */
    private function rejected(
        UpdateVehicleRequestDTO $request,
        CatalogMutationRejectReasonEnum $reason,
    ): CatalogMutationResultDTO {
        return $this->results->rejected(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Vehicle,
            operation: CatalogMutationOperationEnum::Update,
            externalId: $request->msId,
            reason: $reason,
        );
    }

    /**
     * Откатывает idempotency guard и публикует failed result перед пробросом исключения.
     */
    private function failed(UpdateVehicleRequestDTO $request): void
    {
        $this->cache->forgetAccepted($request->operationId);
        $this->results->failed(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Vehicle,
            operation: CatalogMutationOperationEnum::Update,
            externalId: $request->msId,
        );
    }
}
