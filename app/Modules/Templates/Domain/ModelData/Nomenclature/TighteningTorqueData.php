<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use Spatie\LaravelData\Data;

/** Момент затяжки свечи зажигания, Н·м. */
final class TighteningTorqueData extends Data
{
    /**
     * Фиксирует диапазон момента затяжки для ступичного подшипника.
     */
    public function __construct(
        public readonly float $min,
        public readonly float $max,
    ) {}
}
