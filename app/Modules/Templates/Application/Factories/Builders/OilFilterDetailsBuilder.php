<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
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
    /**
     * Этот метод собирает engine oil-filter details из последовательных ячеек импорта.
     * Шаги:
     * 1) Читает исполнение и форму фильтра через enum-справочники.
     * 2) Читает поле `father`, разрешая его через два возможных словаря.
     * 3) Читает диаметр, mother и блок габаритов.
     * 4) Возвращает `OilFilterDetailsData` с типизированным вложенным metrics-объектом.
     */
    public function build(DetailsRowCursor $cursor): OilFilterDetailsData
    {
        return new OilFilterDetailsData(
            performance: $cursor->pullRequiredLabel(PerformanceEnum::class, 'Исполнение фильтра')->name,
            form: $cursor->pullRequiredLabel(FormEnum::class, 'Форма фильтра')->name,
            father: $this->pullFather($cursor),
            diameter: $cursor->pullRequiredIntCell('Диаметр'),
            mother: $cursor->pullRequiredIntCell('Мать'),
            metrics: $this->buildMetrics($cursor),
        );
    }

    /**
     * Этот метод собирает габариты масляного фильтра.
     * Шаги:
     * 1) Читает обязательный список длин.
     * 2) Читает обязательный список ширин.
     * 3) Читает обязательный список высот.
     * 4) Возвращает `OilFilterMetricsData`.
     */
    private function buildMetrics(DetailsRowCursor $cursor): OilFilterMetricsData
    {
        return new OilFilterMetricsData(
            length: $cursor->pullRequiredFloatArray('Длина'),
            width: $cursor->pullRequiredFloatArray('Ширина'),
            height: $cursor->pullRequiredFloatArray('Высота'),
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
    private function pullFather(DetailsRowCursor $cursor): string
    {
        $label = $cursor->pullRequiredStringCell('Резьба или Папа');
        $case = OilFilterThreadEnum::fromLabel($label) ?? OilFilterFatherEnum::fromLabel($label);

        if ($case === null) {
            throw DetailsDataBuildException::unknownDictionaryValue('резьбы/папы масляного фильтра', $label);
        }

        return $case->name;
    }
}
