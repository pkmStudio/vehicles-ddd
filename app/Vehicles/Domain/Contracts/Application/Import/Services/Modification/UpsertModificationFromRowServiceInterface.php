<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Modification;

use App\Vehicles\Domain\Models\Modification;

interface UpsertModificationFromRowServiceInterface
{
    public function execute(array $row): ?Modification;
}
