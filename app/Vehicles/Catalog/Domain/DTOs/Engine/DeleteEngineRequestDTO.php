<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Engine;

final readonly class DeleteEngineRequestDTO
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $engId,
    ) {}
}
