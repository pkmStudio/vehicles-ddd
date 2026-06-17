<?php

declare(strict_types=1);

namespace App\Vehicles\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FailuresExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly array $failures,
    ) {}

    public function headings(): array
    {
        return [
            'Row',
            'Attribute',
            'Error',
            'Value',
        ];
    }

    public function collection(): Collection
    {
        return collect($this->failures)->map(function ($failure) {
            return [
                'row' => $failure['row'],
                'attribute' => $failure['attribute'],
                'error' => is_array($failure['errors']) ? implode('; ', $failure['errors']) : $failure['errors'],
                'value' => json_encode($failure['values'], JSON_UNESCAPED_UNICODE),
            ];
        });
    }
}
