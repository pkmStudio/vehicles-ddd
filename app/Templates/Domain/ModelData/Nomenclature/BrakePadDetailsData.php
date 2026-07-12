<?php

declare(strict_types=1);

namespace App\Templates\Domain\ModelData\Nomenclature;

use App\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `brakePads` (Nomenclature). Портируется декларативно 1-в-1 из `BrakePadTemplate`
 * dan-center, без тестового покрытия — подключение к реальному Import/Export ещё не сделано (это
 * задача фичей Warehouse\Import/Warehouse\Export). Чистый объект-значение — сборка из строки
 * (`DetailsDataFactory`) и рендер в Excel-ячейки (`DetailsDataPresenter`) сюда не входят.
 */
#[MapName(SnakeCaseMapper::class)]
final class BrakePadDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly ?string $position = null,
        public readonly ?string $brakePadsType = null,
        public readonly ?string $materialLinings = null,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
