<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Factories;

use App\Vehicles\Import\Domain\ModelData\Vehicle\VehicleData;
use Illuminate\Validation\ValidationException;

interface VehicleDataFactoryInterface
{
    /**
     * @param  array<string, mixed>  $row
     *
     * @throws ValidationException
     */
    public function make(array $row): VehicleData;
}
