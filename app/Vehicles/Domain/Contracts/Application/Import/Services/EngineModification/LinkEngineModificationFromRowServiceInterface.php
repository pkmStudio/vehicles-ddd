<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\EngineModification;

interface LinkEngineModificationFromRowServiceInterface
{
    public function execute(array $row): void;
}
