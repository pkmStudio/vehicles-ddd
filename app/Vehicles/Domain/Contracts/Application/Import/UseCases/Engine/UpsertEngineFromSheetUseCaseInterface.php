<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine;

use App\Vehicles\Domain\Models\Engine;

interface UpsertEngineFromSheetUseCaseInterface
{
    public function execute(array $row): Engine;
}
