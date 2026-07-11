<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\ModelData\Manufacturer;

use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ManufacturerData extends Data
{
    public function __construct(
        public readonly int $mfaId,
        public readonly string $name,
        public readonly ProviderEnum $provider,
        public readonly ?int $id = null,
    ) {}
}
