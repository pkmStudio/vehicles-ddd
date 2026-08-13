<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\StabilizerLinkDetailsData;

/**
 * Строит форму шаблона `stabilizerLink` (Nomenclature) из Excel-строки. Не подключена ни к
 * одному Import/Export сценарию — см. докблок `StabilizerLinkDetailsData`.
 */
final readonly class StabilizerLinkDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    /**
     * Этот метод собирает details стойки стабилизатора из Excel-строки.
     * Шаги:
     * 1) Читает две резьбы и длину.
     * 2) Читает общий блок габаритов номенклатуры.
     * 3) Возвращает `StabilizerLinkDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): StabilizerLinkDetailsData
    {
        return new StabilizerLinkDetailsData(
            thread1: $cursor->pullRequiredStringCell('Резьба 1'),
            thread2: $cursor->pullRequiredStringCell('Резьба 2'),
            length: $cursor->pullRequiredFloatCell('Длина'),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
