<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Services;

interface ImportKitApplicabilityRowServiceInterface
{
    /**
     * @param  array<int, mixed>  $row
     */
    public function importFromRow(array $row): void;
}
