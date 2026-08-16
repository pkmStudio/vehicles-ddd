<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Brand;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\BrandEventPayloadDTO;

/**
 * Доменный факт обновления Warehouse-бренда.
 */
final readonly class BrandUpdated
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public BrandEventPayloadDTO $brand,
    ) {}
}
