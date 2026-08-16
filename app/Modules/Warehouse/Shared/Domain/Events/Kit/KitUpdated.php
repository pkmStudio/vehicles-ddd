<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Kit;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\KitEventPayloadDTO;

/**
 * Доменный факт обновления Warehouse-набора.
 */
final readonly class KitUpdated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public KitEventPayloadDTO $kit,
    ) {}
}
