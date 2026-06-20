<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\Factories;

use App\Vehicles\Domain\ModelData\Engine\EngineData;
use Illuminate\Validation\ValidationException;

interface EngineDataFactoryInterface
{
    /**
     * @param  array<string, mixed>  $row
     *
     * @throws ValidationException
     */
    public function make(array $row): EngineData;
}
