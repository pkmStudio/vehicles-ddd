<?php

declare(strict_types=1);

namespace App\Vehicles\DTOs;

/**
 * Данные производителя для записи. Чистый носитель (валидация — в ManufacturerValidator).
 */
final readonly class ManufacturerData
{
    public function __construct(
        public int $mfaId,
        public string $name,
        public string $provider,
    ) {}

    public function toArray(): array
    {
        return [
            'mfa_id' => $this->mfaId,
            'name' => $this->name,
            'provider' => $this->provider,
        ];
    }
}
