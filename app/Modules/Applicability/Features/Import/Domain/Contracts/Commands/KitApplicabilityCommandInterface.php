<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Commands;

interface KitApplicabilityCommandInterface
{
    public function saveImportedModificationTarget(int $kitId, int $modificationId): void;
}
