<?php

declare(strict_types=1);

namespace App\Templates\Domain\ModelData\Nomenclature;

use App\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Форма шаблона `polyVBelt` (Nomenclature). Портируется декларативно 1-в-1 из
 * `PolyVBeltTemplate` dan-center, без тестового покрытия — подключение к реальному Import/Export
 * ещё не сделано. Единственное поле — базовые габариты, специфичных характеристик нет.
 */
final class PolyVBeltDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
