<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `wiperAdapter` (Nomenclature). Портируется декларативно 1-в-1 из
 * `WiperAdapterTemplate` dan-center, без тестового покрытия — подключение к реальному
 * Import/Export ещё не сделано. В отличие от `WiperDetailsData` — только передний адаптер, без
 * заднего и без характеристик самой щётки.
 */
#[MapName(SnakeCaseMapper::class)]
final class WiperAdapterDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly string $position,
        /** @var array<int, string> */
        public readonly array $adapterTypeFront,
        public readonly NomenclatureMetricsData $metrics,
    ) {}
}
