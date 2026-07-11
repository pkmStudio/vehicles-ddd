<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\DTOs\EngineModification;

final readonly class EngineModificationCommandRowDTO
{
    public function __construct(
        public ?int $engId,
        public ?int $modId,
        public ?string $type,
    ) {}
}
