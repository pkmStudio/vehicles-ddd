<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Engine;

use Spatie\LaravelData\Data;

/**
 * Электрод свечи зажигания. Чистый объект-значение — сборка из строки (`DetailsDataFactory`) и
 * рендер в Excel-ячейки (`DetailsDataPresenter`) сюда не входят.
 */
final class SparkPlugElectrodeDetailsData extends Data
{
    /**
     * Фиксирует параметры электрода свечи зажигания.
     */
    public function __construct(
        public readonly string $gap,
    ) {}
}
