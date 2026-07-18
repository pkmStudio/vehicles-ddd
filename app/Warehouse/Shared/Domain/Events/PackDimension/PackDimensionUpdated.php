<?php

declare(strict_types=1);

namespace App\Warehouse\Shared\Domain\Events\PackDimension;

/**
 * Доменный факт обновления упаковочного размера Warehouse.
 */
final readonly class PackDimensionUpdated
{
    /**
     * Хранит контекст операции и обновлённую упаковку.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $packDimension,
    ) {}
}
