<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\AirFilterDetailsData;

/** Рендерит форму `airFilter` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class AirFilterDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки nomenclature air-filter шаблона.
     * Шаги:
     * 1) Перечисляет select-поля исполнения, формы, корпуса и вида фильтра.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return ['Исполнение фильтра', 'Форма фильтра', 'Корпус', 'Вид фильтра', ...$this->metricsHeadings()];
    }

    /**
     * Этот метод указывает Data-класс nomenclature air-filter presenter-а.
     * Шаги:
     * 1) Возвращает class-string `AirFilterDetailsData`.
     *
     * @return class-string<AirFilterDetailsData>
     */
    protected function dataClass(): string
    {
        return AirFilterDetailsData::class;
    }

    /**
     * Этот метод рендерит nomenclature air-filter details в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `AirFilterDetailsData`.
     * 2) Переводит enum-name поля и boolean `frame` в Excel-labels.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, AirFilterDetailsData::class);

        return [
            $this->nameToLabelCell(PerformanceEnum::class, $data->performance),
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->boolToLabelCell($data->frame),
            $this->nameToLabelCell(FilterMediaTypeEnum::class, $data->filterType),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
