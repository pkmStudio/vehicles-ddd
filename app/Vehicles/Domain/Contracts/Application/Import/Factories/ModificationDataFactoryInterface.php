<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Factories;

use App\Vehicles\Domain\ModelData\Modification\ModificationData;
use Illuminate\Validation\ValidationException;

interface ModificationDataFactoryInterface
{
    /**
     * @param  array<string, mixed>  $row
     *
     * @throws ValidationException
     */
    public function make(array $row): ModificationData;
}
