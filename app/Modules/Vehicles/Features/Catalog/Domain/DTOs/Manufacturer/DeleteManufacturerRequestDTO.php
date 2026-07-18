<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer;

/**
 * Передает параметры сценария или результат мутации производителей.
 */
final readonly class DeleteManufacturerRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных производителей.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $mfaId,
    ) {}
}
