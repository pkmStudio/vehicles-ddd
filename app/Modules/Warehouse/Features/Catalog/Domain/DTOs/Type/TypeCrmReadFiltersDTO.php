<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type;

final readonly class TypeCrmReadFiltersDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $char = null,
    ) {}
}
