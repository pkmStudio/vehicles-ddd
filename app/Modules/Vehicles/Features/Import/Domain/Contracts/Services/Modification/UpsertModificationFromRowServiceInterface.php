<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface UpsertModificationFromRowServiceInterface
{
    public function upsertFromRow(ModificationCommandRowDTO $row): ?ModificationData;
}
