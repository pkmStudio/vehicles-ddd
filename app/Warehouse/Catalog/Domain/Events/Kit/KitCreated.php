<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Events\Kit;

use App\Warehouse\Catalog\Domain\ModelData\KitData;

/**
 * Доменный факт создания Warehouse-набора.
 */
final readonly class KitCreated
{
    /**
     * Хранит контекст операции и созданный набор.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public KitData $kit,
    ) {}
}
