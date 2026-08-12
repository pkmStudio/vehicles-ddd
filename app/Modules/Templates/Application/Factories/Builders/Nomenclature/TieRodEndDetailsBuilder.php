<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TieRodEndDetailsData;

/**
 * Строит форму шаблона `tieRodEnd` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `TieRodEndDetailsData`.
 */
final readonly class TieRodEndDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    /**
     * Этот метод собирает details наконечника рулевой тяги из Excel-строки.
     * Шаги:
     * 1) Читает две резьбы, числовую длину, размер конуса и конусность.
     * 2) Читает общий блок габаритов.
     * 3) Возвращает `TieRodEndDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): TieRodEndDetailsData
    {
        return new TieRodEndDetailsData(
            thread1: $cursor->pullRequiredStringCell('Резьба 1'),
            thread2: $cursor->pullRequiredStringCell('Резьба 2'),
            length: $cursor->pullRequiredFloatCell('Длина'),
            coneSize: $cursor->pullRequiredFloatCell('Размер конуса'),
            taper: $cursor->pullRequiredStringCell('Конусность'),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
