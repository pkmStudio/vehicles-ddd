<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification;

final readonly class EngineModificationRowDTO
{
    /**
     * Фиксирует normalized row связи engine-modification.
     */
    public function __construct(
        public int $engId,
        public int $modId,
        public string $type,
    ) {}
}
