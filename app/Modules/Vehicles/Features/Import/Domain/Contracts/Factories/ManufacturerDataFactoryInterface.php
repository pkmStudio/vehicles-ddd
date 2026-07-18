<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
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
