<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `timingBelt` (Nomenclature). Портируется декларативно 1-в-1 из
 * `TimingBeltTemplate` dan-center, без тестового покрытия — подключение к реальному Import/Export
 * ещё не сделано. `clutchDiscDiameter` — подпись поля в исходнике буквально "Диаметр диска
 * сцепления d1 (мм)", возможно унаследована от другого шаблона, но переносится как есть.
 */
#[MapName(SnakeCaseMapper::class)]
final class TimingBeltDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly ?float $clutchDiscDiameter = null,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
