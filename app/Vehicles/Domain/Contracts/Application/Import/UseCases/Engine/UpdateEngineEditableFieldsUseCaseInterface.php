<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine;

use App\Vehicles\Domain\Models\Engine;

interface UpdateEngineEditableFieldsUseCaseInterface
{
    public function execute(int $engId, array $attributes): Engine;
}
