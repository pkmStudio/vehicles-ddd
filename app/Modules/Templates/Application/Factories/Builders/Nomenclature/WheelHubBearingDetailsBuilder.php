<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WheelHubBearingDetailsData;

/**
 * Строит форму шаблона `wheelHubBearing` (Nomenclature) из Excel-строки. Не подключена ни к
 * одному Import/Export сценарию — см. докблок `WheelHubBearingDetailsData`. `height` — строка
 * (не число), см. докблок Data-класса.
 */
final readonly class WheelHubBearingDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    /**
     * Этот метод собирает details ступичного подшипника из Excel-строки.
     * Шаги:
     * 1) Читает высоту, ABS, два крепления и диаметры как строковые поля.
     * 2) Читает общий блок габаритов.
     * 3) Возвращает `WheelHubBearingDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): WheelHubBearingDetailsData
    {
        return new WheelHubBearingDetailsData(
            height: $cursor->pullRequiredStringCell('Высота'),
            abs: $cursor->pullRequiredStringCell('ABS'),
            mount1: $cursor->pullRequiredStringCell('Крепление 1'),
            mount2: $cursor->pullRequiredStringCell('Крепление 2'),
            innerDiameter: $cursor->pullRequiredStringCell('Внутренний диаметр'),
            outerDiameter: $cursor->pullRequiredStringCell('Внешний диаметр'),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
