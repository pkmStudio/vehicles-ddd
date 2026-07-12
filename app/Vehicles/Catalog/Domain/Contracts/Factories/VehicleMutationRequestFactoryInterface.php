<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Factories;

use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;

/**
 * Описывает порт сборки DTO запроса мутации автомобилей.
 */
interface VehicleMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): VehicleMutationRequestDTO;
}
