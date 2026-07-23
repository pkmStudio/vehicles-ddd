<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Clients;

interface VehiclesModificationClientInterface
{
    public function resolveByMsAndModId(int $msId, int $modId): int;
}
