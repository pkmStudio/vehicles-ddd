<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\Factories;

use App\Vehicles\Domain\ModelData\EngineModification\EngineModificationData;
use Illuminate\Validation\ValidationException;

interface EngineModificationDataFactoryInterface
{
    /**
     * @param  array<string, mixed>  $row
     *
     * @throws ValidationException
     */
    public function make(array $row): EngineModificationData;
}
