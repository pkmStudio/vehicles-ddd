<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\ModelData\Manufacturer;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Данные производителя (чтение и запись). Валидация — в ManufacturerDataFactory.
 */
#[MapName(SnakeCaseMapper::class)]
final class ManufacturerData extends Data
{
    public function __construct(
        public readonly int $mfaId,
        public readonly string $name,
        public readonly string $provider,
        public readonly ?int $id = null,
    ) {}
}
