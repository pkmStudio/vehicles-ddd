<?php

declare(strict_types=1);

namespace App\Vehicles\Exports;

use App\Models\Warehouse\Kit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EngineKitApplicabilityExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        $rows = collect();

        Kit::query()
            ->whereHas('engines')
            ->with(['nomenclatures', 'engines'])
            ->chunk(1000, function ($kits) use (&$rows) {
                foreach ($kits as $kit) {
                    $partNumbers = implode(';', $kit->nomenclatures->pluck('part_number')->toArray());

                    foreach ($kit->engines as $engine) {
                        $rows->push([
                            'kit_id' => $kit->id,
                            'part_numbers' => $partNumbers,
                            'eng_id' => $engine->eng_id,
                            'code_engine' => $engine->code_engine,
                        ]);
                    }
                }
            });

        return $rows;
    }

    public function headings(): array
    {
        return [
            'ID комплекта',
            'Состав комплекта',
            'ID двигателя TecDoc',
            'Код двигателя',
        ];
    }
}
