<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\VehiclePartSpecificationData;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\PartSpecification;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class VehiclesApplicabilityClient implements VehiclesApplicabilityClientInterface
{
    public function frontWiperSpecifications(WiperLengthDTO $length): Collection
    {
        $query = $this->baseWiperQuery()
            ->whereRaw("jsonb_exists(details, 'front')")
            ->whereRaw("NOT jsonb_exists(details, 'back')")
            ->where('details->front->length_main->min', '<=', $length->lengthMain)
            ->where('details->front->length_main->max', '>=', $length->lengthMain)
            ->where('details->front->count_wipers', $length->countWipers);

        if ($length->lengthSecond !== null) {
            $query
                ->where('details->front->length_second->min', '<=', $length->lengthSecond)
                ->where('details->front->length_second->max', '>=', $length->lengthSecond);
        }

        return $this->mapSpecifications($query->get());
    }

    public function rearWiperSpecifications(WiperLengthDTO $length): Collection
    {
        $query = $this->baseWiperQuery()
            ->whereRaw("jsonb_exists(details, 'back')")
            ->whereRaw("NOT jsonb_exists(details, 'front')")
            ->where('details->back->length_rear->min', '<=', $length->lengthMain)
            ->where('details->back->length_rear->max', '>=', $length->lengthMain)
            ->where('details->back->count_wipers', $length->countWipers);

        return $this->mapSpecifications($query->get());
    }

    private function baseWiperQuery(): Builder
    {
        return PartSpecification::query()
            ->where('partable_type', PartableTypeEnum::VEHICLE)
            ->where('template', DetailTemplateEnum::WIPER);
    }

    /**
     * @param  Collection<int, PartSpecification>  $specifications
     * @return Collection<int, VehiclePartSpecificationData>
     */
    private function mapSpecifications(Collection $specifications): Collection
    {
        return $specifications
            ->map(static fn (PartSpecification $specification): VehiclePartSpecificationData => new VehiclePartSpecificationData(
                id: (int) $specification->id,
                vehicleId: (int) $specification->partable_id,
                template: DetailTemplateEnum::WIPER,
                details: (array) ($specification->details ?? []),
            ))
            ->values();
    }
}
