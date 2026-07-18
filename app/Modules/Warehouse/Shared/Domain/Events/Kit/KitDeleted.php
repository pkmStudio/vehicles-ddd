<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Kit;

/**
 * Доменный факт удаления Warehouse-набора.
 */
final readonly class KitDeleted
{
    /**
     * Хранит контекст операции и id удалённого набора.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $kitId,
    ) {}
}
