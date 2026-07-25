<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Application\Traits\ParsesBooleanCells;
use App\Modules\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\AirFilterDetailsData;

/**
 * Строит форму шаблона `airFilter` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `AirFilterDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class AirFilterDetailsBuilder
{
    use BuildsNomenclatureMetrics;
    use ParsesBooleanCells;

    public function build(DetailsRowCursor $cursor): AirFilterDetailsData
    {
        return new AirFilterDetailsData(
            performance: $cursor->pullRequiredLabel(PerformanceEnum::class, 'Исполнение фильтра')->name,
            form: $cursor->pullRequiredLabel(FormEnum::class, 'Форма фильтра')->name,
            frame: $this->pullRequiredBoolLabel($cursor, 'Корпус'),
            filterType: $cursor->pullRequiredLabel(FilterMediaTypeEnum::class, 'Вид фильтра')->name,
            metrics: $this->buildMetrics($cursor),
        );
    }
}
