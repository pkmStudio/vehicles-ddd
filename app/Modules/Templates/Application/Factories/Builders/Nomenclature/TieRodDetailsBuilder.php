<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\TieRod\ApplicationEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TieRodDetailsData;

/**
 * Строит форму шаблона `tieRod` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `TieRodDetailsData`. `length` — строка (в отличие от
 * `TieRodEndDetailsBuilder`).
 */
final readonly class TieRodDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): TieRodDetailsData
    {
        return new TieRodDetailsData(
            thread1: $cursor->pullRequiredStringCell('Резьба 1'),
            thread2: $cursor->pullRequiredStringCell('Резьба 2'),
            length: $cursor->pullRequiredStringCell('Длина'),
            coneSize: $cursor->pullRequiredFloatCell('Размер конуса'),
            taper: $cursor->pullRequiredStringCell('Конусность'),
            application: $cursor->pullRequiredLabel(ApplicationEnum::class, 'Применение')->name,
            metrics: $this->buildMetrics($cursor),
        );
    }
}
