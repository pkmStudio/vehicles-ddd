<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Engine;

use App\Vehicles\Domain\Models\Engine;

interface UpdateEngineEditableFieldsServiceInterface
{
    public function execute(int $engId, array $attributes): Engine;
}
