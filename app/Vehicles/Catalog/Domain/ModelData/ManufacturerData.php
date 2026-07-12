<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\ModelData;

use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Хранит типизированный снимок записи производителей для Repository и Command.
 */
#[MapName(SnakeCaseMapper::class)]
final class ManufacturerData extends Data
{
    /**
     * Инициализирует immutable-снимок данных производителей.
     */
    public function __construct(
        public readonly int $mfaId,
        public readonly string $name,
        public readonly ProviderEnum $provider,
        public readonly ?int $id = null,
    ) {}
}
