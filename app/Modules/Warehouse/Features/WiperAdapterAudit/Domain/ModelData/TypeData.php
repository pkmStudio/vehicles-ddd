<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок типа Warehouse-номенклатуры для определения detail-шаблона в аудите адаптеров.
 */
#[MapName(SnakeCaseMapper::class)]
final class TypeData extends Data
{
    /**
     * Хранит имя, код char и id типа номенклатуры.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $char,
        public readonly ?int $id = null,
    ) {}
}
