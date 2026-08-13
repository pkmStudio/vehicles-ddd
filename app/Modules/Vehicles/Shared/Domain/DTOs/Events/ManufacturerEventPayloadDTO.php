<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Events;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

final readonly class ManufacturerEventPayloadDTO
{
    /**
     * Стабильный shared payload производителя для catalog facts.
     */
    public function __construct(
        public int $id,
        public int $mfaId,
        public string $name,
        public ProviderEnum $provider,
    ) {}
}
