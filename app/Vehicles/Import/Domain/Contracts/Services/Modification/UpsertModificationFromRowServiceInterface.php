<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Modification;

use App\Vehicles\Import\Domain\ModelData\Modification\ModificationData;

interface UpsertModificationFromRowServiceInterface
{
    public function upsertFromRow(array $row): ?ModificationData;
}
