<?php

declare(strict_types=1);

namespace App\Warehouse\WiperAdapterAudit\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-номенклатуры набора, нужный для аудита адаптеров дворников.
 */
#[MapName(SnakeCaseMapper::class)]
final class NomenclatureData extends Data
{
    /**
     * Хранит тип, артикул и details, из которых извлекаются адаптеры.
     *
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $typeId,
        public readonly string $partNumber,
        public readonly array $details,
        public readonly ?int $id = null,
        public readonly ?TypeData $type = null,
    ) {}
}
