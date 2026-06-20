<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\Factories;

use App\Vehicles\Domain\ModelData\Manufacturer\ManufacturerData;
use Illuminate\Validation\ValidationException;

interface ManufacturerDataFactoryInterface
{
    /**
     * @param  array<string, mixed>  $row
     *
     * @throws ValidationException
     */
    public function make(array $row): ManufacturerData;
}
