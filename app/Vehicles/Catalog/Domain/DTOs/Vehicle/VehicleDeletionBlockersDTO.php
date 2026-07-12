<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Vehicle;

final readonly class VehicleDeletionBlockersDTO
{
    public function __construct(
        public int $childrenCount,
        public int $modificationsCount,
        public int $partSpecificationsCount,
    ) {}

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
