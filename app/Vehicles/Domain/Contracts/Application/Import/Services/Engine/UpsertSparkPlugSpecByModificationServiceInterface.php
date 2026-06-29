<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Engine;

use App\Vehicles\Domain\DTOs\ModificationSparkPlugResult;

interface UpsertSparkPlugSpecByModificationServiceInterface
{
    public function execute(int $msId, int $modId, array $details): ModificationSparkPlugResult;
}
