<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Engine;

use App\Vehicles\Import\Domain\DTOs\ModificationSparkPlugResult;

interface UpsertSparkPlugSpecByModificationServiceInterface
{
    public function upsertByModification(int $msId, int $modId, array $details): ModificationSparkPlugResult;
}
