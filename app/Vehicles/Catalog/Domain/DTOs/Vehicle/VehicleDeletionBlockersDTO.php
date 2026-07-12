<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Vehicle;

/**
 * Передает параметры сценария или результат мутации автомобилей.
 */
final readonly class VehicleDeletionBlockersDTO
{
    /**
     * Инициализирует immutable-снимок данных автомобилей.
     */
    public function __construct(
        public int $childrenCount,
        public int $modificationsCount,
        public int $partSpecificationsCount,
    ) {}

    /**
     * Проверяет, есть ли зависимости, блокирующие удаление.
     */
    public function hasBlockers(): bool
    {
        return $this->childrenCount > 0
            || $this->modificationsCount > 0
            || $this->partSpecificationsCount > 0;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'children_count' => $this->childrenCount,
            'modifications_count' => $this->modificationsCount,
            'part_specifications_count' => $this->partSpecificationsCount,
        ];
    }
}
