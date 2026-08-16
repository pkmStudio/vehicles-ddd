<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Modification;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\ModificationEventPayloadDTO;

/**
 * Фиксирует доменный факт изменения модификаций.
 */
final readonly class ModificationCreated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public ModificationEventPayloadDTO $modification,
    ) {}
}
