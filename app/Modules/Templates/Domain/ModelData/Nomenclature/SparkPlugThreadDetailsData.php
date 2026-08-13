<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use Spatie\LaravelData\Data;

/** Резьба свечи зажигания (Nomenclature-версия — характеристика товара, не потребность ТС). */
final class SparkPlugThreadDetailsData extends Data
{
    /**
     * Фиксирует параметры резьбы свечи зажигания.
     */
    public function __construct(
        public readonly string $size,
        public readonly string $pitch,
        public readonly string $length,
    ) {}
}
