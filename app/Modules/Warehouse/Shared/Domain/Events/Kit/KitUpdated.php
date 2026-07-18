<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Kit;

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
        public array $kit,
    ) {}
}
