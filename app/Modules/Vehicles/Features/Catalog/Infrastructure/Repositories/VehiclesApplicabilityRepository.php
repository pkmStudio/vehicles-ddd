<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehiclesApplicabilityRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Applicability\VehicleApplicabilityLookupDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class VehiclesApplicabilityRepository implements VehiclesApplicabilityRepositoryInterface
{
    public function frontWiperSpecifications(int $lengthMain, ?int $lengthSecond, int $countWipers): Collection
    {
        $query = $this->baseWiperQuery(WiperSideEnum::FRONT)
            ->where('details->front->length_main->min', '<=', $lengthMain)
            ->where('details->front->length_main->max', '>=', $lengthMain)
            ->where('details->front->count_wipers', $countWipers);

        if ($lengthSecond !== null) {
            $query
                ->where('details->front->length_second->min', '<=', $lengthSecond)
                ->where('details->front->length_second->max', '>=', $lengthSecond);
        }

        return $this->mapSpecifications($query->get());
    }

    public function rearWiperSpecifications(int $lengthMain, int $countWipers): Collection
    {
        return $this->mapSpecifications(
            $this->baseWiperQuery(WiperSideEnum::BACK)
                ->where('details->back->length_rear->min', '<=', $lengthMain)
                ->where('details->back->length_rear->max', '>=', $lengthMain)
                ->where('details->back->count_wipers', $countWipers)
                ->get(),
        );
    }

    public function findVehicleByMsId(int $msId): ?VehicleApplicabilityLookupDTO
    {
        $vehicle = DB::table('vehicles')
            ->where('ms_id', $msId)
            ->first(['id', 'ms_id', 'parent_id']);

        if ($vehicle === null) {
            return null;
        }

        return new VehicleApplicabilityLookupDTO(
            id: (int) $vehicle->id,
            msId: (int) $vehicle->ms_id,
            parentId: $vehicle->parent_id === null ? null : (int) $vehicle->parent_id,
        );
    }

    public function findVehicleMsIdById(int $id): ?int
    {
        $msId = DB::table('vehicles')
            ->where('id', $id)
            ->value('ms_id');

        return $msId === null ? null : (int) $msId;
    }

    public function findModificationIdByMsAndModId(int $msId, int $modId): ?int
    {
        $id = DB::table('modifications')
            ->where('ms_id', $msId)
            ->where('mod_id', $modId)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private function baseWiperQuery(WiperSideEnum $side): Builder
    {
        $otherSide = $side === WiperSideEnum::FRONT ? WiperSideEnum::BACK : WiperSideEnum::FRONT;

        return DB::table('part_specifications')
            ->where('partable_type', PartableTypeEnum::VEHICLE->value)
            ->where('template', DetailTemplateEnum::WIPER->value)
            ->whereRaw("jsonb_exists(details, '{$side->value}')")
            ->whereRaw("NOT jsonb_exists(details, '{$otherSide->value}')");
    }

    /**
     * @param  Collection<int, stdClass>  $specifications
     * @return Collection<int, VehiclePartSpecificationForApplicabilityDTO>
     */
    private function mapSpecifications(Collection $specifications): Collection
    {
        return $specifications
            ->map(fn (stdClass $specification): VehiclePartSpecificationForApplicabilityDTO => new VehiclePartSpecificationForApplicabilityDTO(
                id: (int) $specification->id,
                vehicleId: (int) $specification->partable_id,
                details: $this->jsonArray($specification->details),
            ))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
