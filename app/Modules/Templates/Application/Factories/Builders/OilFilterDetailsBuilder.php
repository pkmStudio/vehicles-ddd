<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\Engine\OilFilterDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\OilFilterMetricsData;

/**
 * Строит форму шаблона `oilFilter` из Excel-строки (не подключена ни к одному Import/Export
 * сценарию — см. докблок `OilFilterDetailsData`, портируется без покрытия тестами). Выделено из
 * `DetailsDataFactory`, чтобы неподключённая логика не лежала в одном файле с рабочей (Wiper/
 * SparkPlug). Простой класс без собственного порта — вызывается только из `DetailsDataFactory`.
 */
final readonly class OilFilterDetailsBuilder
{
    public function build(DetailsRowCursor $cursor): OilFilterDetailsData
    {
        return new OilFilterDetailsData(
            performance: $cursor->pullLabel(PerformanceEnum::class)?->name,
            form: $cursor->pullLabel(FormEnum::class)?->name,
            father: $this->pullFather($cursor),
            diameter: $cursor->pullIntCell(),
            mother: $cursor->pullIntCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }

    /** Читает 3 ячейки подряд: список длины, список ширины, список высоты. */
    private function buildMetrics(DetailsRowCursor $cursor): OilFilterMetricsData
    {
        return new OilFilterMetricsData(
            length: $cursor->pullFloatArray(),
            width: $cursor->pullFloatArray(),
            height: $cursor->pullFloatArray(),
        );
    }

    /**
     * Читает `father`, пробуя два словаря по очереди (см. докблок `OilFilterDetailsData` —
     * зависимость от `performance` в старом DSL не проверялась).
     * Шаги:
     * 1) Читает сырую ячейку через курсор.
     * 2) Если ячейка пустая — возвращает null.
     * 3) Иначе сначала пробует найти лейбл в `OilFilterThreadEnum`.
     * 4) Если не нашёл — пробует `OilFilterFatherEnum`; возвращает хранимое имя найденного case'а
     *    (или null, если не нашёлся ни в одном из двух словарей).
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
