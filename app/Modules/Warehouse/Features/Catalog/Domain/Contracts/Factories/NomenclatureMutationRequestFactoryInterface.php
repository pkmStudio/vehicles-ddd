<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureMutationRequestDTO;

/**
 * Порт сборки DTO мутации Warehouse-номенклатуры из валидированного payload.
 */
interface NomenclatureMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): NomenclatureMutationRequestDTO;
}
