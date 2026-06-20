<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\UseCases\Modification;

use App\Vehicles\Domain\Models\Modification;

interface UpsertModificationFromRowUseCaseInterface
{
    public function execute(array $row): ?Modification;
}
