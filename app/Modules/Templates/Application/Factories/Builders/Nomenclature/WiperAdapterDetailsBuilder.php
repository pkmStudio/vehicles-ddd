<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperAdapterDetailsData;

/**
 * Строит форму шаблона `wiperAdapter` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `WiperAdapterDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class WiperAdapterDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): WiperAdapterDetailsData
    {
        $toName = static fn ($case) => $case->name;

        return new WiperAdapterDetailsData(
            position: $cursor->pullRequiredLabel(PositionEnum::class, 'Расположение')->name,
            construction: $cursor->pullRequiredLabel(ConstructionEnum::class, 'Конструкция')->name,
            adapterTypeFront: array_map(
                $toName,
                $cursor->pullRequiredMultiLabel(FrontAdapterTypeEnum::class, 'Тип крепления передних'),
            ),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
