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
     * Шаги:
     * 1) Прочитать operation и nested part_specification из валидированного payload.
     * 2) Преобразовать owner/template/details поля в локальные DTO/enums.
     * 3) Вернуть общий mutation request с request DTO конкретной операции.
     *
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): PartSpecificationMutationRequestDTO;
}
