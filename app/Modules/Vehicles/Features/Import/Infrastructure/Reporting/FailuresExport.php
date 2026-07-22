<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Reporting;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final readonly class FailuresExport implements FromCollection, WithHeadings, FailuresExportInterface
{
    public function __construct(
        private array $failures,
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
        $toFailureRow = function ($failure) {
            return [
                'row' => $failure['row'],
                'attribute' => $failure['attribute'],
                'error' => is_array($failure['errors']) ? implode('; ', $failure['errors']) : $failure['errors'],
                'value' => json_encode($failure['values'], JSON_UNESCAPED_UNICODE),
            ];
        };

        return collect($this->failures)->map($toFailureRow);
    }
}
