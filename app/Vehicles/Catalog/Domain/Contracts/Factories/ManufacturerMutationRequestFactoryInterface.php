<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Factories;

use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\ManufacturerMutationRequestDTO;

interface ManufacturerMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): ManufacturerMutationRequestDTO;
}
