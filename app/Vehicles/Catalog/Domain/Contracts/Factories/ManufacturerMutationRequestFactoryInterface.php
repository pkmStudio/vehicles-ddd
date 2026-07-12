<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Factories;

use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\ManufacturerMutationRequestDTO;

/**
 * Описывает порт сборки DTO запроса мутации производителей.
 */
interface ManufacturerMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): ManufacturerMutationRequestDTO;
}
