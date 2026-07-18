<?php

declare(strict_types=1);

namespace App\Warehouse\Shared\Domain\Events\PackDimension;

/**
 * Доменный факт создания упаковочного размера Warehouse.
 */
final readonly class PackDimensionCreated
{
    /**
     * Хранит контекст операции и созданную упаковку.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $packDimension,
    ) {}
}
