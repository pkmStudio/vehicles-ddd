<?php

declare(strict_types=1);

namespace App\Templates\Domain\ModelData\Nomenclature;

use Spatie\LaravelData\Data;

/** Момент затяжки свечи зажигания, Н·м. */
final class TighteningTorqueData extends Data
{
    public function __construct(
        public readonly ?float $min = null,
        public readonly ?float $max = null,
    ) {}
}
