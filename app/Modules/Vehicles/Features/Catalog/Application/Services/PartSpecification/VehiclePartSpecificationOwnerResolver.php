<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\VehiclePartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerResolutionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerVehicleDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\ResolvedPartSpecificationOwnerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Разрешает автомобиль-владелец PartSpecification, создавая или обновляя его при наличии payload.
 */
final readonly class VehiclePartSpecificationOwnerResolver implements VehiclePartSpecificationOwnerResolverInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
        private VehicleCommandInterface $command,
    ) {}

    /**
     * Разрешает владельца спеки во внутренний id записи.
     *
     * Шаги:
     * 1) Найти автомобиль по external_id владельца.
     * 2) Если автомобиль отсутствует, создать его из owner payload.
     * 3) Если автомобиль существует и payload передан, обновить его.
     * 4) Вернуть внутренний id автомобиля или причину отклонения.
     */
    public function execute(PartSpecificationOwnerDTO $owner): PartSpecificationOwnerResolutionDTO
    {
        $existing = $this->vehicles->findByMsId($owner->externalId);
        if ($existing === null) {
            return $this->createOwner($owner);
        }

        if ($owner->vehicle === null) {
            return $this->resolved(
                externalId: $owner->externalId,
                partableId: (int) $existing->id,
            );
        }

        $vehicleData = $this->vehicleData(
            msId: $owner->externalId,
            payload: $owner->vehicle,
            id: $existing->id,
        );

        if ($vehicleData instanceof CatalogMutationRejectReasonEnum) {
            return new PartSpecificationOwnerResolutionDTO(
                owner: null,
                rejectReason: $vehicleData,
            );
        }

        $vehicle = $this->command->update($vehicleData);

        return $this->resolved(
            externalId: $vehicle->msId,
            partableId: (int) $vehicle->id,
        );
    }

    /**
     * Создает отсутствующий автомобиль-владелец или возвращает причину отказа.
     *
     * Шаги:
     * 1) Проверить наличие payload создания автомобиля.
     * 2) Собрать VehicleData с проверкой производителя и родителя.
     * 3) Создать автомобиль через Command и вернуть его внутренний id.
     */
    private function createOwner(PartSpecificationOwnerDTO $owner): PartSpecificationOwnerResolutionDTO
    {
        if ($owner->vehicle === null) {
            return new PartSpecificationOwnerResolutionDTO(
                owner: null,
                rejectReason: CatalogMutationRejectReasonEnum::OwnerNotFound,
            );
        }

        $vehicleData = $this->vehicleData(
            msId: $owner->externalId,
            payload: $owner->vehicle,
        );

        if ($vehicleData instanceof CatalogMutationRejectReasonEnum) {
            return new PartSpecificationOwnerResolutionDTO(
                owner: null,
                rejectReason: $vehicleData,
            );
        }

        $vehicle = $this->command->create($vehicleData);

        return $this->resolved(
            externalId: $vehicle->msId,
            partableId: (int) $vehicle->id,
        );
    }

    /**
     * Собирает VehicleData для создания или обновления владельца.
     *
     * Шаги:
     * 1) Найти производителя по mfa_id.
     * 2) Разрешить parent vehicle при наличии parent_ms_id.
     * 3) Вернуть VehicleData или причину отказа.
     */
    private function vehicleData(
        int $msId,
        PartSpecificationOwnerVehicleDTO $payload,
        ?int $id = null,
    ): VehicleData|CatalogMutationRejectReasonEnum {
        $manufacturer = $this->manufacturers->findByMfaId($payload->mfaId);
        if ($manufacturer === null) {
            return CatalogMutationRejectReasonEnum::ManufacturerNotFound;
        }

        $parentId = null;
        if ($payload->parentMsId !== null) {
            $parent = $this->vehicles->findByMsId($payload->parentMsId);
            if ($parent === null) {
                return CatalogMutationRejectReasonEnum::ParentVehicleNotFound;
            }
            $parentId = $parent->id;
        }

        return new VehicleData(
            msId: $msId,
            mfaId: $payload->mfaId,
            manufacturerId: (int) $manufacturer->id,
            name: $payload->name,
            type: $payload->type,
            steeringType: $payload->steeringType,
            typeCarcase: $payload->typeCarcase,
            provider: $payload->provider,
            generation: $payload->generation,
            generationYearFrom: $payload->generationYearFrom,
            generationYearTo: $payload->generationYearTo,
            parentId: $parentId,
            excelTableId: $payload->excelTableId,
            localizedName: $payload->localizedName,
            generationShort: $payload->generationShort,
            isAllow: $payload->isAllow,
            id: $id,
        );
    }

    /**
     * Собирает успешный результат разрешения автомобиля-владельца.
     */
    private function resolved(int $externalId, int $partableId): PartSpecificationOwnerResolutionDTO
    {
        $owner = new ResolvedPartSpecificationOwnerDTO(
            type: PartableTypeEnum::VEHICLE,
            externalId: $externalId,
            partableId: $partableId,
        );

        return new PartSpecificationOwnerResolutionDTO(
            owner: $owner,
        );
    }
}
