<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Engine;

final readonly class EngineDeleted
{
    public function __construct(public int $userId, public string $operationId, public int $engId, public int $engineId) {}
}
