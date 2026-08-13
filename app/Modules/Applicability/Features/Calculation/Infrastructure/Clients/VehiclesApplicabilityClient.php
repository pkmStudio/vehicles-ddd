<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\VehiclePartSpecificationData;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface as PublicVehiclesApplicabilityClientInterface;
use App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO;
use Illuminate\Support\Collection;

/**
 * Адаптер публичного Vehicles API к локальному calculation-порту Applicability.
 */
final readonly class VehiclesApplicabilityClient implements VehiclesApplicabilityClientInterface
{
    /**
     * Получает публичный Vehicles applicability client.
     *
     * Шаги:
     * 1. Сохраняет read-only Vehicles boundary.
     * 2. Оставляет перевод публичных DTO в локальные snapshots методам чтения.
     */
    public function __construct(
        private PublicVehiclesApplicabilityClientInterface $vehicles,
    ) {}

    /**
     * Читает front-спецификации дворников автомобилей по длинам комплекта.
     *
     * Шаги:
     * 1. Передает основные размеры и количество щеток в публичный Vehicles boundary.
     * 2. Получает публичные DTO спецификаций.
     * 3. Мапит их в локальные `VehiclePartSpecificationData`.
     */
    public function frontWiperSpecifications(WiperLengthDTO $length): Collection
    {
        return $this->mapSpecifications($this->vehicles->frontWiperSpecifications(
            lengthMain: $length->lengthMain,
            lengthSecond: $length->lengthSecond,
            countWipers: $length->countWipers,
        ));
    }

    /**
     * Читает rear-спецификации дворников автомобилей по длине комплекта.
     *
     * Шаги:
     * 1. Передает rear length как основной размер в публичный Vehicles boundary.
     * 2. Получает публичные DTO спецификаций.
     * 3. Мапит их в локальные `VehiclePartSpecificationData`.
     */
    public function rearWiperSpecifications(WiperLengthDTO $length): Collection
    {
        return $this->mapSpecifications($this->vehicles->rearWiperSpecifications(
            lengthMain: $length->lengthMain,
            countWipers: $length->countWipers,
        ));
    }

    /**
     * @param  Collection<int, VehiclePartSpecificationForApplicabilityDTO>  $specifications
     * @return Collection<int, VehiclePartSpecificationData>
     */
    private function mapSpecifications(Collection $specifications): Collection
    {
        return $specifications
            ->map(static fn (VehiclePartSpecificationForApplicabilityDTO $specification): VehiclePartSpecificationData => new VehiclePartSpecificationData(
                id: $specification->id,
                vehicleId: $specification->vehicleId,
                template: DetailTemplateEnum::WIPER,
                details: $specification->details,
            ))
            ->values();
    }
}
