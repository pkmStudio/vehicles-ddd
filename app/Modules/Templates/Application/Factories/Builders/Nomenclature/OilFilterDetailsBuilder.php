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
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
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

    /**
     * Этот метод собирает nomenclature oil-filter details из Excel-строки.
     * Шаги:
     * 1) Читает исполнение, форму и признак корпуса фильтра.
     * 2) Читает `father` через словари резьбы/папы.
     * 3) Читает диаметр, mother и общий блок габаритов.
     * 4) Возвращает `OilFilterDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): OilFilterDetailsData
    {
        return new OilFilterDetailsData(
            performance: $cursor->pullRequiredLabel(PerformanceEnum::class, 'Исполнение фильтра')->name,
            form: $cursor->pullRequiredLabel(FormEnum::class, 'Форма фильтра')->name,
            frame: $this->pullRequiredBoolLabel($cursor, 'Корпус'),
            father: $this->pullFather($cursor),
            diameter: $cursor->pullRequiredIntCell('Диаметр'),
            mother: $cursor->pullRequiredIntCell('Мать'),
            metrics: $this->buildMetrics($cursor),
        );
    }

    /**
     * Читает `father`, пробуя два словаря по очереди (см. докблок `OilFilterDetailsData` —
     * зависимость от `performance` в исходном DSL не проверялась).
     * Шаги:
     * 1) Читает обязательную строковую ячейку `Резьба или Папа`.
     * 2) Пробует найти label сначала в `OilFilterThreadEnum`, затем в `OilFilterFatherEnum`.
     * 3) Если label неизвестен обоим словарям — бросает доменную ошибку.
     * 4) Возвращает enum-name найденного case.
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
