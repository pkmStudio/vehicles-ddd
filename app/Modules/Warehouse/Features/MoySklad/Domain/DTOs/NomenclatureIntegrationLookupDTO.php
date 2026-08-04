<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\DTOs;

final readonly class NomenclatureIntegrationLookupDTO
{
    private function __construct(
        public int $nomenclatureId,
        public ?string $externalCode = null,
        public ?int $integrationId = null,
    ) {}

    public static function byNomenclatureId(int $nomenclatureId): self
    {
        return new self(nomenclatureId: $nomenclatureId);
    }

    public static function forDelete(int $nomenclatureId, string $externalCode, ?int $integrationId = null): self
    {
        return new self(
            nomenclatureId: $nomenclatureId,
            externalCode: $externalCode,
            integrationId: $integrationId,
        );
    }
}
