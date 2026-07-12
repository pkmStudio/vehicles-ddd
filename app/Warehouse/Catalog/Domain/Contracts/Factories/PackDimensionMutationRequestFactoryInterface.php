<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Factories;

use App\Warehouse\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;

/**
 * Порт сборки DTO мутации упаковочного размера Warehouse из валидированного payload.
 */
interface PackDimensionMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): PackDimensionMutationRequestDTO;
}
