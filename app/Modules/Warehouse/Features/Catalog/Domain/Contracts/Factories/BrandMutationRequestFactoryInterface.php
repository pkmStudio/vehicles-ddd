<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandMutationRequestDTO;

/**
 * Порт сборки DTO мутации Warehouse-бренда из валидированного payload.
 */
interface BrandMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): BrandMutationRequestDTO;
}
