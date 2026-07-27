<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Services;

use Illuminate\Support\Collection;

interface VehicleKitApplicabilityExportServiceInterface
{
    public function getRows(): Collection;

    /** @return array<int, mixed> */
    public function mapRow(mixed $row): array;

    /** @return array<int, string> */
    public function getHeadings(): array;

    public function getReferenceRows(): Collection;

    /** @return array<int, string> */
    public function getReferenceHeadings(): array;
}
