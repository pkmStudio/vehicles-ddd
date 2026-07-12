<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Events\Kit;

use App\Warehouse\Catalog\Domain\ModelData\KitData;

/**
 * Доменный факт обновления Warehouse-набора.
 */
final readonly class KitUpdated
{
    /**
     * Хранит контекст операции и обновлённый набор.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public KitData $kit,
    ) {}
}
