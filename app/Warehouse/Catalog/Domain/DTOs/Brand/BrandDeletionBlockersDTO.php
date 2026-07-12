<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Brand;

/**
 * Счётчики зависимостей, блокирующих удаление Warehouse-бренда.
 */
final readonly class BrandDeletionBlockersDTO
{
    /**
     * Хранит количество связанных записей.
     */
    public function __construct(
        public int $nomenclaturesCount,
    ) {}

    /**
     * Проверяет, есть ли блокирующие зависимости.
     */
    public function hasBlockers(): bool
    {
        return $this->nomenclaturesCount > 0;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'nomenclatures_count' => $this->nomenclaturesCount,
        ];
    }
}
