<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class FeatureData extends Data
{
    public function __construct(
        public readonly string $entityType,
        public readonly string $name,
        public readonly ?int $id = null,
    ) {}
}
