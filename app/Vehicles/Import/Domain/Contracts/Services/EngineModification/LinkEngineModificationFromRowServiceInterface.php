<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\EngineModification;

interface LinkEngineModificationFromRowServiceInterface
{
    public function linkFromRow(array $row): void;
}
