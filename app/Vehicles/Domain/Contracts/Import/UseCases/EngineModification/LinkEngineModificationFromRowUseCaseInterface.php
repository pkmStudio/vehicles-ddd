<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\UseCases\EngineModification;

interface LinkEngineModificationFromRowUseCaseInterface
{
    public function execute(array $row): void;
}
