<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Factories;

use App\Vehicles\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;

/**
 * Описывает порт сборки DTO запроса мутации модификаций.
 */
interface ModificationMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): ModificationMutationRequestDTO;
}
