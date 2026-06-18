<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Vehicle;

use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehicleKitApplicabilityExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        $rows = collect();
        PartSpecification::query()
            ->where('partable_type', Vehicle::class)
            ->with(['kits.nomenclatures', 'partable'])
            ->chunk(1000, function ($partSpecifications) use (&$rows) {
                foreach ($partSpecifications as $partSpecification) {
                    foreach ($partSpecification->kits as $kit) {
                        $rows->push([
                            'kit_id' => $kit->id,
                            'part_numbers' => implode(';', $kit->nomenclatures->pluck('part_number')->toArray()),

                            'excel_table_id' => $partSpecification->partable->excel_table_id,
                            'vehicle_ms_id' => $partSpecification->partable->ms_id,
                            'vehicle_name' => $partSpecification->partable->name,
                            'generation' => $partSpecification->partable->generation,
                            'year_from' => $partSpecification->partable->generation_year_from,
                            'year_to' => $partSpecification->partable->generation_year_to,
                            'type_carcase' => $partSpecification->partable->type_carcase,
                        ]);
                    }
                }
            });

        return $rows;
    }

    public function headings(): array
    {
        return [
            // Заголовки для комплекта
            'ID комплекта',
            'Состав комплекта',

            // Заголовки для машины
            'ID гугл таблицы',
            'ID модели',
            'Модель',
            'Поколение',
            'Год от',
            'Год до',
            'Тип кузова',
        ];
    }
}
