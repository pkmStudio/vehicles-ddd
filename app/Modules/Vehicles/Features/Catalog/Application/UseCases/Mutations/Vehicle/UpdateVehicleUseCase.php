<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\UpdateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\VehicleEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\VehicleWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\VehicleWritePolicy;
use Throwable;

/**
 * Оркестрирует сценарий мутации автомобилей из внешнего сообщения.
 */
final readonly class UpdateVehicleUseCase
{
    /**
     * Получает порты, нужные для update vehicle mutation.
     *
     * Шаги:
     * 1) Принять repositories для поиска существующего автомобиля, производителя и parent vehicle.
     * 2) Принять command записи автомобиля.
     * 3) Принять cache/result/write-policy порты для идемпотентности, result event и provider rules.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
        private VehicleCommandInterface $command,
        private CatalogMutationCacheServiceInterface $cache,
        private CatalogMutationResultServiceInterface $results,
        private VehicleWritePolicy $writePolicy,
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
        } catch (ProviderOwnershipException $e) {
            return $this->rejected(
                request: $request,
                reason: CatalogMutationRejectReasonEnum::ProviderOwnershipConflict,
                errors: $e->errors(),
            );
        } catch (Throwable $e) {
            $this->failed($request);

            throw $e;
        }
    }

    /**
     * Находит существующий автомобиль или возвращает rejected result.
     *
     * Шаги:
     * 1) Найти текущий VehicleData по ms_id из update request.
     * 2) Вернуть snapshot существующей записи для merge.
     * 3) Если запись не найдена, вернуть rejected NotFound result.
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
     *
     * Шаги:
     * 1) Если текущий provider не разрешает catalog-managed поля, сохранить existing manufacturer id.
     * 2) Для OD-managed записи найти производителя из входящего mfa_id.
     * 3) Вернуть internal manufacturer id или rejected result.
     */
    private function resolveManufacturerId(
        UpdateVehicleRequestDTO $request,
        VehicleData $existing,
    ): int|CatalogMutationResultDTO {
        if (! $this->writePolicy->allowsCatalogManagedFields(VehicleWritePolicyResultDTO::fromArray($existing->toArray()))) {
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
     *
     * Шаги:
     * 1) Найти производителя по mfa_id из update request.
     * 2) Вернуть ManufacturerData, если производитель существует.
     * 3) Иначе вернуть rejected ManufacturerNotFound result.
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
     *
     * Шаги:
     * 1) Если текущий provider не разрешает catalog-managed поля, сохранить existing parent id.
     * 2) Если parent_ms_id не передан, очистить parent для OD-managed записи.
     * 3) Найти parent vehicle по внешнему ms_id или вернуть rejected ParentVehicleNotFound.
     */
    private function resolveParentId(
        UpdateVehicleRequestDTO $request,
        VehicleData $existing,
    ): int|CatalogMutationResultDTO|null {
        if (! $this->writePolicy->allowsCatalogManagedFields(VehicleWritePolicyResultDTO::fromArray($existing->toArray()))) {
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
     *
     * Шаги:
     * 1) Собрать incoming VehicleData из request и уже разрешенных manufacturer/parent id.
     * 2) Передать incoming и existing snapshots в write policy update branch.
     * 3) Вернуть merged VehicleData с учетом provider-owned locked fields.
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
            isAllow: $request->isAllow,
            generationYearTo: $request->generationYearTo,
            parentId: $parentId,
            parentMsId: $request->parentMsId,
            excelTableId: $request->excelTableId,
            localizedName: $request->localizedName,
            generationShort: $request->generationShort,
            id: $existing->id,
        );

        $writeResult = $this->writePolicy->apply(
            incoming: VehicleWritePolicyResultDTO::fromArray($incomingData->toArray()),
            existing: VehicleWritePolicyResultDTO::fromArray($existing->toArray()),
            sourceProvider: $request->provider,
        );

        return VehicleData::from($writeResult->toArray());
    }

    /**
     * Публикует факт обновления автомобиля.
     *
     * Шаги:
     * 1) Сериализовать обновленный VehicleData в payload события.
     * 2) Опубликовать module-level факт VehicleUpdated с user/operation correlation.
     */
    private function publishUpdatedEvent(
        UpdateVehicleRequestDTO $request,
        VehicleData $vehicle,
    ): void {
        $payload = new VehicleEventPayloadDTO(
            id: (int) $vehicle->id,
            msId: $vehicle->msId,
            mfaId: $vehicle->mfaId,
            manufacturerId: $vehicle->manufacturerId,
            name: $vehicle->name,
            type: $vehicle->type,
            steeringType: $vehicle->steeringType,
            typeCarcase: $vehicle->typeCarcase,
            provider: $vehicle->provider,
            generation: $vehicle->generation,
            generationYearFrom: $vehicle->generationYearFrom,
            isAllow: $vehicle->isAllow,
            generationYearTo: $vehicle->generationYearTo,
            parentId: $vehicle->parentId,
            parentMsId: $vehicle->parentMsId ?? null,
            excelTableId: $vehicle->excelTableId,
            localizedName: $vehicle->localizedName,
            generationShort: $vehicle->generationShort,
        );

        event(new VehicleUpdated(
            userId: $request->userId,
            operationId: $request->operationId,
            vehicle: $payload,
        ));
    }

    /**
     * Собирает completed result для update-сценария.
     *
     * Шаги:
     * 1) Использовать ms_id обновленного автомобиля как externalId результата.
     * 2) Передать внутренний id записи как recordId.
     * 3) Делегировать публикацию completed result service.
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
     *
     * Шаги:
     * 1) Использовать ms_id из request как externalId результата.
     * 2) Передать причину отказа в result service.
     * 3) Вернуть опубликованный rejected result для Vehicle Update.
     */
    private function rejected(
        UpdateVehicleRequestDTO $request,
        CatalogMutationRejectReasonEnum $reason,
        array $errors = [],
    ): CatalogMutationResultDTO {
        return $this->results->rejected(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Vehicle,
            operation: CatalogMutationOperationEnum::Update,
            externalId: $request->msId,
            reason: $reason,
            errors: $errors,
        );
    }

    /**
     * Откатывает idempotency guard и публикует failed result перед пробросом исключения.
     *
     * Шаги:
     * 1) Освободить operation id в cache, чтобы broker-сообщение можно было повторить.
     * 2) Опубликовать failed result с ms_id обновляемого автомобиля.
     * 3) Оставить проброс исходного исключения вызывающему execute.
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
