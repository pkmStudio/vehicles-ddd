<?php

declare(strict_types=1);

namespace App\Warehouse\Shared\Domain\Events\Kit;

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
        public array $kit,
    ) {}
}
