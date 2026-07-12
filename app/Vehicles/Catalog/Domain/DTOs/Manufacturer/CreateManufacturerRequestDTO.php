<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Manufacturer;

use App\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Передает параметры сценария или результат мутации производителей.
 */
final readonly class CreateManufacturerRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных производителей.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $mfaId,
        public string $name,
        public ProviderEnum $provider,
    ) {}
}
