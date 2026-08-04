<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand;

final readonly class BrandLookupDTO
{
    private function __construct(
        public ?int $id = null,
        public ?string $name = null,
    ) {}

    public static function byId(int $id): self
    {
        return new self(id: $id);
    }

    public static function byName(string $name): self
    {
        return new self(name: $name);
    }
}
