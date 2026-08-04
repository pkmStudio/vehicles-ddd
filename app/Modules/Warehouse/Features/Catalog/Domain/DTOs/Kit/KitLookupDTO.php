<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

final readonly class KitLookupDTO
{
    private function __construct(
        public ?int $id = null,
        public ?string $importHash = null,
    ) {}

    public static function byId(int $id): self
    {
        return new self(id: $id);
    }

    public static function byImportHash(string $importHash): self
    {
        return new self(importHash: $importHash);
    }
}
