<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WheelHubDetailsData;

/**
 * Строит форму шаблона `wheelHub` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `WheelHubDetailsData`. `height`/`outerDiameter` — числа
 * (в отличие от `WheelHubBearingDetailsBuilder`, см. докблок Data-класса).
 */
final readonly class WheelHubDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    /**
     * Этот метод собирает details ступицы из Excel-строки.
     * Шаги:
     * 1) Читает числовую высоту, ABS, три крепления, внутренний диаметр и число шлицов.
     * 2) Читает числовой внешний диаметр и общий блок габаритов.
     * 3) Возвращает `WheelHubDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): WheelHubDetailsData
    {
        return new WheelHubDetailsData(
            height: $cursor->pullRequiredFloatCell('Высота'),
            abs: $cursor->pullRequiredStringCell('ABS'),
            mount1: $cursor->pullRequiredStringCell('Крепление 1'),
            mount2: $cursor->pullRequiredStringCell('Крепление 2'),
            mount3: $cursor->pullRequiredStringCell('Крепление 3'),
            innerDiameter: $cursor->pullRequiredStringCell('Внутренний диаметр'),
            splinesCount: $cursor->pullRequiredIntCell('Количество шлицов'),
            outerDiameter: $cursor->pullRequiredFloatCell('Внешний диаметр'),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
