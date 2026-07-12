<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\PackDimension;

/**
 * Счётчики зависимостей, блокирующих удаление упаковочного размера Warehouse.
 */
final readonly class PackDimensionDeletionBlockersDTO
{
    /**
     * Хранит количество наборов, использующих упаковку.
     */
    public function __construct(
        public int $kitsCount,
    ) {}

    /**
     * Проверяет, есть ли блокирующие зависимости.
     */
    public function hasBlockers(): bool
    {
        return $this->kitsCount > 0;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'kits_count' => $this->kitsCount,
        ];
    }
}
