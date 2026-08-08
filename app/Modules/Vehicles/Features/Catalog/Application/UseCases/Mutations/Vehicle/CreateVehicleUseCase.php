<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\Vehicle\VehicleMutationWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\CreateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationWriteContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
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
     */
    private function resolveMsId(CreateVehicleRequestDTO $request): int
    {
        return $request->msId ?? $this->vehicles->nextMsId();
    }

    /**
     * Отклоняет create, если автомобиль с таким external id уже существует.
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
            generationYearTo: $request->generationYearTo,
            parentId: $parentId,
            excelTableId: $request->excelTableId,
            localizedName: $request->localizedName,
            generationShort: $request->generationShort,
            isAllow: $request->isAllow,
        );

        return $this->writePolicy->applyForCreate(
            incoming: $incomingData,
            context: new VehicleMutationWriteContextDTO(
                operationId: $request->operationId,
            ),
        );
    }

    /**
     * Публикует факт создания автомобиля.
     */
    private function publishCreatedEvent(
        CreateVehicleRequestDTO $request,
        VehicleData $vehicle,
    ): void {
        event(new VehicleCreated(
            userId: $request->userId,
            operationId: $request->operationId,
            vehicle: $vehicle->toArray(),
        ));
    }

    /**
     * Собирает completed result для create-сценария.
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
