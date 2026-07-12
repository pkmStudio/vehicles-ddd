<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Factories;

use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationMutationRequestDTO;

/**
 * Описывает порт сборки DTO запроса мутации спецификаций деталей.
 */
interface PartSpecificationMutationRequestFactoryInterface
{
    /**
     * Собирает DTO запроса мутации спеки из валидированного payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): PartSpecificationMutationRequestDTO;
}
