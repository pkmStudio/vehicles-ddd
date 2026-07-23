<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final readonly class CalculationFailuresExport implements FromCollection, WithHeadings
{
    public function __construct(
        private KitApplicabilityCalculationResultDTO $result,
    ) {}

    public function collection(): Collection
    {
        return collect($this->result->errors)->map(fn (string $error, int $index): array => [
            $this->result->runId,
            $index + 1,
            $error,
        ]);
    }

    public function headings(): array
    {
        return ['Run ID', 'Number', 'Error'];
    }
}
