<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\ModificationSparkPlugResultDTO;

interface UpsertSparkPlugSpecByModificationServiceInterface
{
    public function upsertByModification(int $msId, int $modId, array $details): ModificationSparkPlugResultDTO;
}
