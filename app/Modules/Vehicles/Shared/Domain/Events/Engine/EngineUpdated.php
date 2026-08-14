<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Engine;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\EngineEventPayloadDTO;

/**
 * Фиксирует доменный факт изменения двигателей.
 */
final readonly class EngineUpdated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public EngineEventPayloadDTO $engine,
    ) {}
}
