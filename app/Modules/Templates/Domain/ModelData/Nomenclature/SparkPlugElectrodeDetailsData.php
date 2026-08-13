<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Электрод свечи зажигания (Nomenclature-версия — характеристика товара, не потребность ТС). */
#[MapName(SnakeCaseMapper::class)]
final class SparkPlugElectrodeDetailsData extends Data
{
    /**
     * Фиксирует параметры электрода свечи зажигания.
     */
    public function __construct(
        public readonly string $gap,
        public readonly string $countSide,
    ) {}
}
