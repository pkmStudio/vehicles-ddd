<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Vehicle;

use Spatie\LaravelData\Data;

/**
 * Диапазон длины щётки (мм) — `{min, max}`. Всегда строится как объект (даже если оба конца
 * пустые), это отражает старое поведение DSL: вложенный `children`-узел не может стать null,
 * только его листья. Чистый объект-значение — ни сборка из строки (`DetailsDataFactory`), ни
 * рендер в Excel-ячейки (`DetailsDataPresenter`) сюда не входят.
 */
final class WiperLengthRangeData extends Data
{
    /**
     * Фиксирует минимальную и максимальную длину щетки в миллиметрах.
     */
    public function __construct(
        public readonly int $min,
        public readonly int $max,
    ) {}
}
