<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\VehicleEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\VehicleWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\VehicleWritePolicy;
use Throwable;

/**
 * Оркестрирует сценарий мутации автомобилей из внешнего сообщения.
 */
final readonly class CreateVehicleUseCase
{
    /**
     * Получает порты, нужные для create vehicle mutation.
     *
     * Шаги:
     * 1) Принять repositories для проверки ms_id, производителя и parent vehicle.
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
    public function execute(CreateVehicleRequestDTO $request): ?CatalogMutationResultDTO
    {
        if (! $this->cache->accept($request->operationId)) {
            return null;
        }

        $msId = $request->msId;

        try {
            $msId = $this->resolveMsId($request);

            $duplicateReject = $this->rejectIfAlreadyExists($request, $msId);
            if ($duplicateReject !== null) {
                return $duplicateReject;
            }

            $manufacturer = $this->resolveManufacturer($request, $msId);
            if ($manufacturer instanceof CatalogMutationResultDTO) {
                return $manufacturer;
            }

            $parentId = $this->resolveParentId($request, $msId);
            if ($parentId instanceof CatalogMutationResultDTO) {
                return $parentId;
            }

            $vehicleData = $this->prepareVehicleData(
                request: $request,
                msId: $msId,
                manufacturer: $manufacturer,
                parentId: $parentId,
            );

            $vehicle = $this->command->create($vehicleData);
            $this->publishCreatedEvent($request, $vehicle);

            return $this->completed($request, $vehicle);
        } catch (Throwable $e) {
            $this->failed($request, $msId);

            throw $e;
        }
    }

    /**
     * Возвращает внешний идентификатор для create-сценария, генерируя новый при отсутствии.
     *
     * Шаги:
     * 1) Если payload содержит ms_id, использовать его как external id.
     * 2) Если ms_id отсутствует, запросить следующий свободный id у vehicle repository.
     */
    private function resolveMsId(CreateVehicleRequestDTO $request): int
    {
        return $request->msId ?? $this->vehicles->nextMsId();
    }

    /**
     * Отклоняет create, если автомобиль с таким external id уже существует.
     *
     * Шаги:
     * 1) Проверить наличие автомобиля по уже разрешенному ms_id.
     * 2) Если запись отсутствует, разрешить продолжение create workflow.
     * 3) Если запись найдена, вернуть rejected AlreadyExists result.
     */
    private function rejectIfAlreadyExists(
        CreateVehicleRequestDTO $request,
        int $msId,
    ): ?CatalogMutationResultDTO {
        if ($this->vehicles->findByMsId($msId) === null) {
            return null;
        }

        return $this->rejected(
            request: $request,
            externalId: $msId,
            reason: CatalogMutationRejectReasonEnum::AlreadyExists,
        );
    }

    /**
     * Находит производителя или возвращает rejected result.
     *
     * Шаги:
     * 1) Найти производителя по mfa_id из create request.
     * 2) Вернуть ManufacturerData для записи автомобиля.
     * 3) Если производитель не найден, вернуть rejected ManufacturerNotFound с resolved ms_id.
     */
    private function resolveManufacturer(
        CreateVehicleRequestDTO $request,
        int $msId,
    ): ManufacturerData|CatalogMutationResultDTO {
        $manufacturer = $this->manufacturers->findByMfaId($request->mfaId);
        if ($manufacturer !== null) {
            return $manufacturer;
        }

        return $this->rejected(
            request: $request,
            externalId: $msId,
            reason: CatalogMutationRejectReasonEnum::ManufacturerNotFound,
        );
    }

    /**
     * Разрешает parent vehicle или возвращает rejected result.
     *
     * Шаги:
     * 1) Если parent_ms_id не передан, оставить parentId пустым.
     * 2) Найти parent vehicle по внешнему ms_id.
     * 3) Вернуть внутренний id parent vehicle или rejected ParentVehicleNotFound.
     */
    private function resolveParentId(
        CreateVehicleRequestDTO $request,
        int $msId,
    ): int|CatalogMutationResultDTO|null {
        if ($request->parentMsId === null) {
            return null;
        }

        $parent = $this->vehicles->findByMsId($request->parentMsId);
        if ($parent !== null) {
            return $parent->id;
        }

        return $this->rejected(
            request: $request,
            externalId: $msId,
            reason: CatalogMutationRejectReasonEnum::ParentVehicleNotFound,
        );
    }

    /**
     * Собирает incoming VehicleData и применяет catalog create policy перед записью.
     *
     * Шаги:
     * 1) Собрать VehicleData из request, resolved ms_id, manufacturer id и parent id.
     * 2) Передать snapshot в write policy create branch.
     * 3) Вернуть VehicleData с provider rules, готовый для command create.
     */
    private function prepareVehicleData(
        CreateVehicleRequestDTO $request,
        int $msId,
        ManufacturerData $manufacturer,
        ?int $parentId,
    ): VehicleData {
        $incomingData = new VehicleData(
            msId: $msId,
            mfaId: $request->mfaId,
            manufacturerId: (int) $manufacturer->id,
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
        );

        $writeResult = $this->writePolicy->apply(
            incoming: VehicleWritePolicyResultDTO::fromArray($incomingData->toArray()),
            existing: null,
            sourceProvider: $request->provider,
        );

        return VehicleData::from($writeResult->toArray());
    }

    /**
     * Публикует факт создания автомобиля.
     *
     * Шаги:
     * 1) Сериализовать созданный VehicleData в payload события.
     * 2) Опубликовать module-level факт VehicleCreated с user/operation correlation.
     */
    private function publishCreatedEvent(
        CreateVehicleRequestDTO $request,
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

        event(new VehicleCreated(
            userId: $request->userId,
            operationId: $request->operationId,
            vehicle: $payload,
        ));
    }

    /**
     * Собирает completed result для create-сценария.
     *
     * Шаги:
     * 1) Использовать ms_id созданного автомобиля как externalId результата.
     * 2) Передать внутренний id записи как recordId.
     * 3) Делегировать публикацию completed result service.
     */
    private function completed(
        CreateVehicleRequestDTO $request,
        VehicleData $vehicle,
    ): CatalogMutationResultDTO {
        return $this->results->completed(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Vehicle,
            operation: CatalogMutationOperationEnum::Create,
            externalId: $vehicle->msId,
            recordId: $vehicle->id,
        );
    }

    /**
     * Собирает rejected result для create-сценария.
     *
     * Шаги:
     * 1) Использовать resolved external id, даже если он был сгенерирован перед отказом.
     * 2) Передать причину отказа в result service.
     * 3) Вернуть опубликованный rejected result для Vehicle Create.
     */
    private function rejected(
        CreateVehicleRequestDTO $request,
        int $externalId,
        CatalogMutationRejectReasonEnum $reason,
    ): CatalogMutationResultDTO {
        return $this->results->rejected(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Vehicle,
            operation: CatalogMutationOperationEnum::Create,
            externalId: $externalId,
            reason: $reason,
        );
    }

    /**
     * Откатывает idempotency guard и публикует failed result перед пробросом исключения.
     *
     * Шаги:
     * 1) Освободить operation id в cache, чтобы broker-сообщение можно было повторить.
     * 2) Опубликовать failed result с resolved ms_id или 0, если id еще не был определен.
     * 3) Оставить проброс исходного исключения вызывающему execute.
     */
    private function failed(CreateVehicleRequestDTO $request, ?int $msId): void
    {
        $this->cache->forgetAccepted($request->operationId);
        $this->results->failed(
            userId: $request->userId,
            operationId: $request->operationId,
            entity: CatalogEntityEnum::Vehicle,
            operation: CatalogMutationOperationEnum::Create,
            externalId: $msId ?? 0,
        );
    }
}
