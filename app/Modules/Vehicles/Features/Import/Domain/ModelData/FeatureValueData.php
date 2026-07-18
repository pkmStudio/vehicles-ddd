<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class FeatureValueData extends Data
{
    public function __construct(
        public readonly int $featureId,
        public readonly string $name,
        public readonly string $shortCode,
        public readonly ?int $id = null,
    ) {}
}
