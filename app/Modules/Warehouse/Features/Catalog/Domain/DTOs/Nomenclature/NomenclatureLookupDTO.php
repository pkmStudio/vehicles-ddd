<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature;

final readonly class NomenclatureLookupDTO
{
    private function __construct(
        public ?int $id = null,
        public ?string $partNumber = null,
    ) {}

    public static function byId(int $id): self
    {
        return new self(id: $id);
    }

    public static function byPartNumber(string $partNumber): self
    {
        return new self(partNumber: $partNumber);
    }
}
