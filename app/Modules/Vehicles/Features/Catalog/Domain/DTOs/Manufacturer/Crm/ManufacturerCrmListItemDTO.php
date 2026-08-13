<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm;

final readonly class ManufacturerCrmListItemDTO
{
    public function __construct(
        public int $id,
        public int $mfaId,
        public string $name,
        public string $provider,
        public int $vehiclesCount = 0,
    ) {}

    /**
     * @return array{id: int, mfa_id: int, name: string, provider: string, vehicles_count: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'mfa_id' => $this->mfaId,
            'name' => $this->name,
            'provider' => $this->provider,
            'vehicles_count' => $this->vehiclesCount,
        ];
    }
}
