<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Nomenclature;

/**
 * Счётчики зависимостей, блокирующих удаление Warehouse-номенклатуры.
 */
final readonly class NomenclatureDeletionBlockersDTO
{
    /**
     * Хранит количество связанных наборов и интеграций.
     */
    public function __construct(
        public int $kitsCount,
        public int $integrationsCount,
    ) {}

    /**
     * Проверяет, есть ли блокирующие зависимости.
     */
    public function hasBlockers(): bool
    {
        return $this->kitsCount > 0 || $this->integrationsCount > 0;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'kits_count' => $this->kitsCount,
            'integrations_count' => $this->integrationsCount,
        ];
    }
}
