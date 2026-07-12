<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Engine;

use App\Vehicles\Catalog\Domain\ModelData\EngineData;

final readonly class EngineUpdated
{
    public function __construct(public int $userId, public string $operationId, public EngineData $engine) {}
}
