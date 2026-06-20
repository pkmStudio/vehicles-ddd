<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\ModelData\FeatureValue;

final readonly class FeatureValueData
{
    public function __construct(
        public int $featureId,
        public string $name,
        public ?string $shortCode = null,
    ) {}

    public function toArray(): array
    {
        return [
            'feature_id' => $this->featureId,
            'name' => $this->name,
            'short_code' => $this->shortCode,
        ];
    }
}
