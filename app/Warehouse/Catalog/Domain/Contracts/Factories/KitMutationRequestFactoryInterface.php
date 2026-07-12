<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Factories;

use App\Warehouse\Catalog\Domain\DTOs\Kit\KitMutationRequestDTO;

/**
 * Порт сборки DTO мутации Warehouse-набора из валидированного payload.
 */
interface KitMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): KitMutationRequestDTO;
}
