<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final readonly class FailuresExport implements FailuresExportInterface, FromCollection, WithHeadings
{
    public function __construct(
        private array $failures,
    ) {}

    public function collection(): Collection
    {
        return collect($this->failures)->map(static fn (array $failure): array => [
            $failure['row'],
            $failure['attribute'],
            implode('; ', $failure['errors']),
            json_encode($failure['values'], JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function headings(): array
    {
        return ['Row', 'Attribute', 'Error', 'Value'];
    }
}
