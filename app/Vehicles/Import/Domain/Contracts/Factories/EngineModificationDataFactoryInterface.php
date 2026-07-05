<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Factories;

use App\Vehicles\Import\Domain\ModelData\EngineModification\EngineModificationData;
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
