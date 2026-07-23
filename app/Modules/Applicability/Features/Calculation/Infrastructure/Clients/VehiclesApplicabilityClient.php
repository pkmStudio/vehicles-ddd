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
    public function __construct(
        private PublicVehiclesApplicabilityClientInterface $vehicles,
    ) {}

    public function frontWiperSpecifications(WiperLengthDTO $length): Collection
    {
        return $this->mapSpecifications($this->vehicles->frontWiperSpecifications(
            lengthMain: $length->lengthMain,
            lengthSecond: $length->lengthSecond,
            countWipers: $length->countWipers,
        ));
    }

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
