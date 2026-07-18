<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationMutationRequestDTO;

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
