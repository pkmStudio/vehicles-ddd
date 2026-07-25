<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use Spatie\LaravelData\Data;

/**
 * Базовые габариты товара. Каждое измерение — массив значений (не одно число): в dan-center
 * встречаются номенклатуры с несколькими вариантами одного размера (Repeater в исходной форме).
 * Общий блок для 15 из 17 шаблонов Nomenclature (нет только у Wiper и generic/V_BELT).
 */
final class NomenclatureMetricsData extends Data
{
    public function __construct(
        /** @var array<int, int> */
        public readonly array $length,
        /** @var array<int, int> */
        public readonly array $width,
        /** @var array<int, int> */
        public readonly array $height,
    ) {}
}
