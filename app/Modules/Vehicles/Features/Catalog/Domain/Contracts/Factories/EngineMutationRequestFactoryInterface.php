<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineMutationRequestDTO;

/**
 * Описывает порт сборки DTO запроса мутации двигателей.
 */
interface EngineMutationRequestFactoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(array $payload): EngineMutationRequestDTO;
}
