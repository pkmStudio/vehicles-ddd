<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\ModelData\EngineModification;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Данные связи двигатель ↔ модификация (строка пивота engine_modification). Только запись —
 * читается через natural key (eng_id/mod_id) внутри Command, отдельного Repository нет.
 */
#[MapName(SnakeCaseMapper::class)]
final class EngineModificationData extends Data
{
    public function __construct(
        public readonly int $engId,
        public readonly int $modId,
        public readonly string $type,
    ) {}
}
