<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\CabinFilterDetailsData;

/** Рендерит форму `cabinFilter` (Nomenclature) в плоский набор Excel-ячеек экспорта — структурно идентична `AirFilterDetailsPresenter`. */
final readonly class CabinFilterDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки nomenclature cabin-filter шаблона.
     * Шаги:
     * 1) Перечисляет те же select/boolean колонки, что у воздушного фильтра.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return ['Исполнение фильтра', 'Форма фильтра', 'Корпус', 'Вид фильтра', ...$this->metricsHeadings()];
    }

    /**
     * Этот метод указывает Data-класс nomenclature cabin-filter presenter-а.
     * Шаги:
     * 1) Возвращает class-string `CabinFilterDetailsData`.
     *
     * @return class-string<CabinFilterDetailsData>
     */
    protected function dataClass(): string
    {
        return CabinFilterDetailsData::class;
    }

    /**
     * Этот метод рендерит nomenclature cabin-filter details в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `CabinFilterDetailsData`.
     * 2) Переводит enum-name поля и boolean `frame` в labels.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, CabinFilterDetailsData::class);

        return [
            $this->nameToLabelCell(PerformanceEnum::class, $data->performance),
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->boolToLabelCell($data->frame),
            $this->nameToLabelCell(FilterMediaTypeEnum::class, $data->filterType),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
