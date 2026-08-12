<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\DTOs\Packaging;

final readonly class PackDimensionDTO
{
    /**
     * Описывает упаковочный размер, подобранный соседней Packaging-фичей для расчёта свойств набора.
     */
    public function __construct(
        public string $name,
        public int $weight,
        public int $width,
        public int $height,
        public int $length,
        public int $price,
        public int $typeId,
        public bool $generated = false,
        public ?int $id = null,
    ) {}
}
