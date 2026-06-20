<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\ModelData\Feature;

final readonly class FeatureData
{
    public function __construct(
        public string $entityType,
        public string $name,
    ) {}

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'name' => $this->name,
        ];
    }
}
