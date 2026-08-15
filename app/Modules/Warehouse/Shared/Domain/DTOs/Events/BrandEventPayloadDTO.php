<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Events;

final readonly class BrandEventPayloadDTO
{
    /**
     * Стабильный shared payload бренда Warehouse для catalog facts.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $numberSert,
        public string $dateStart,
        public string $dateEnd,
        public string $char,
    ) {}
}
