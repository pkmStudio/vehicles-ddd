<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitMutationRequestDTO;

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
