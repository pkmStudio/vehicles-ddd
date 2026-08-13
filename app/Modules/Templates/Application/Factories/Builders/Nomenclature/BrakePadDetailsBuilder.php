<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\BrakePad\BrakePadTypeEnum;
use App\Modules\Templates\Domain\Enums\BrakePad\LiningMaterialEnum;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BrakePadDetailsData;

/**
 * Строит форму шаблона `brakePads` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `BrakePadDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class BrakePadDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    /**
     * Этот метод собирает details тормозных колодок из Excel-строки.
     * Шаги:
     * 1) Читает расположение, вид колодки и материал накладок через enum-справочники.
     * 2) Читает общий блок габаритов номенклатуры.
     * 3) Возвращает `BrakePadDetailsData` с enum-name значениями.
     */
    public function build(DetailsRowCursor $cursor): BrakePadDetailsData
    {
        return new BrakePadDetailsData(
            position: $cursor->pullRequiredLabel(PositionEnum::class, 'Расположение')->name,
            brakePadsType: $cursor->pullRequiredLabel(BrakePadTypeEnum::class, 'Вид колодки')->name,
            materialLinings: $cursor->pullRequiredLabel(LiningMaterialEnum::class, 'Материал накладок')->name,
            metrics: $this->buildMetrics($cursor),
        );
    }
}
