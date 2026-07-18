<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use Spatie\LaravelData\Data;

/** Резьба свечи зажигания (Nomenclature-версия — характеристика товара, не потребность ТС). */
final class SparkPlugThreadDetailsData extends Data
{
    public function __construct(
        public readonly ?string $size = null,
        public readonly ?string $pitch = null,
        public readonly ?string $length = null,
    ) {}
}
