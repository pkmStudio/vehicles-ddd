<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionMutationRequestDTO;

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
