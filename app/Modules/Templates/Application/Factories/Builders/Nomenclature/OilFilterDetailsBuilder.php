<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Application\Traits\ParsesBooleanCells;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\OilFilterDetailsData;

/**
 * Строит форму шаблона `oilFilter` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `OilFilterDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class OilFilterDetailsBuilder
{
    use BuildsNomenclatureMetrics;
    use ParsesBooleanCells;

    public function build(DetailsRowCursor $cursor): OilFilterDetailsData
    {
        return new OilFilterDetailsData(
            performance: $cursor->pullLabel(PerformanceEnum::class)?->name,
            form: $cursor->pullLabel(FormEnum::class)?->name,
            frame: $this->pullBoolLabel($cursor),
            father: $this->pullFather($cursor),
            diameter: $cursor->pullIntCell(),
            mother: $cursor->pullIntCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }

    /**
     * Читает `father`, пробуя два словаря по очереди (см. докблок `OilFilterDetailsData` —
     * зависимость от `performance` в исходном DSL не проверялась).
     */
    private function pullFather(DetailsRowCursor $cursor): ?string
    {
        $label = $cursor->pullCell();
        if ($label === null) {
            return null;
        }

        return (OilFilterThreadEnum::fromLabel((string) $label) ?? OilFilterFatherEnum::fromLabel((string) $label))?->name;
    }
}
