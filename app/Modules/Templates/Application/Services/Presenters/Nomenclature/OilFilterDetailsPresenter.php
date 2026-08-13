<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\Exceptions\UnknownEnumValueException;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\OilFilterDetailsData;

/** Рендерит форму `oilFilter` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class OilFilterDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки nomenclature oil-filter шаблона.
     * Шаги:
     * 1) Перечисляет исполнение, форму, корпус, father и числовые диаметры.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return [
            'Исполнение фильтра',
            'Форма фильтра',
            'Корпус',
            'Резьба или Папа',
            'Диаметр (мм)',
            'Диаметр внешний уплотнителя (мм) или мама',
            ...$this->metricsHeadings(),
        ];
    }

    /**
     * Этот метод указывает Data-класс nomenclature oil-filter presenter-а.
     * Шаги:
     * 1) Возвращает class-string `OilFilterDetailsData`.
     *
     * @return class-string<OilFilterDetailsData>
     */
    protected function dataClass(): string
    {
        return OilFilterDetailsData::class;
    }

    /**
     * Этот метод рендерит nomenclature oil-filter details в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `OilFilterDetailsData`.
     * 2) Переводит enum-name поля, boolean корпус и `father` в labels.
     * 3) Добавляет числовые диаметры и metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, OilFilterDetailsData::class);

        return [
            $this->nameToLabelCell(PerformanceEnum::class, $data->performance),
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->boolToLabelCell($data->frame),
            $this->fatherCell($data->father),
            $data->diameter,
            $data->mother,
            ...$this->metricsCells($data->metrics),
        ];
    }

    /**
     * Этот метод переводит `father` масляного фильтра в Excel-label.
     * Шаги:
     * 1) Null превращает в пустую Excel-ячейку.
     * 2) Пробует найти enum-name сначала в словаре резьбы, затем в словаре папы.
     * 3) Если имя неизвестно обоим словарям — бросает доменную ошибку.
     * 4) Возвращает value найденного case.
     */
    private function fatherCell(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $case = OilFilterThreadEnum::fromName($name) ?? OilFilterFatherEnum::fromName($name);

        if ($case === null) {
            throw UnknownEnumValueException::name('резьбы/папы масляного фильтра', $name);
        }

        return $case->value;
    }
}
