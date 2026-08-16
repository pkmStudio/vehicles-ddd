<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Events;

final readonly class PackDimensionEventPayloadDTO
{
    /**
     * Стабильный shared payload упаковочного размера Warehouse для catalog facts.
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $weight,
        public int $width,
        public int $height,
        public int $length,
        public int $price,
        public int $typeId,
        public bool $generated = false,
    ) {}
}
