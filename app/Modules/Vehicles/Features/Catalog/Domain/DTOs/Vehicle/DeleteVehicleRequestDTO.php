<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle;

/**
 * Передает параметры сценария или результат мутации автомобилей.
 */
final readonly class DeleteVehicleRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных автомобилей.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $msId,
    ) {}
}
