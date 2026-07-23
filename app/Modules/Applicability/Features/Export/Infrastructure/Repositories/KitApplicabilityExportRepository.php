<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Repositories;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\VehicleKitApplicabilityRowDTO;
use App\Modules\Applicability\Features\Export\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use Illuminate\Support\Collection;

final readonly class KitApplicabilityExportRepository implements KitApplicabilityExportRepositoryInterface
{
    public function vehicleRows(): Collection
    {
        $rows = collect();

        KitApplicability::query()
            ->where('target_type', ApplicabilityTargetTypeEnum::PART_SPECIFICATION)
            ->with(['kit.nomenclatures', 'partSpecification.vehicle'])
            ->chunkById(1000, function (Collection $applicabilities) use ($rows): void {
                foreach ($applicabilities as $applicability) {
                    $kit = $applicability->kit;
                    $specification = $applicability->partSpecification;
                    $vehicle = $specification?->vehicle;

                    if ($kit === null || $specification === null || $vehicle === null) {
                        continue;
                    }

                    $rows->push(new VehicleKitApplicabilityRowDTO(
                        kitId: (int) $kit->id,
                        partNumbers: $kit->nomenclatures->pluck('part_number')->implode(';'),
                        excelTableId: $vehicle->excel_table_id,
                        vehicleMsId: (int) $vehicle->ms_id,
                        vehicleName: (string) $vehicle->name,
                        generation: $vehicle->generation,
                        yearFrom: $vehicle->generation_year_from === null ? null : (int) $vehicle->generation_year_from,
                        yearTo: $vehicle->generation_year_to === null ? null : (int) $vehicle->generation_year_to,
                        typeCarcase: $vehicle->type_carcase,
                    ));
                }
            });

        return $rows;
    }
}
