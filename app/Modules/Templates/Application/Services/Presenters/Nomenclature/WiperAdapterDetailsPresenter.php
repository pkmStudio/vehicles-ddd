<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperAdapterDetailsData;

/** Рендерит форму `wiperAdapter` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class WiperAdapterDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки адаптера дворника.
     * Шаги:
     * 1) Перечисляет расположение и типы переднего крепления.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return ['Расположение', 'Тип крепления передних', ...$this->metricsHeadings()];
    }

    /**
     * Этот метод указывает Data-класс presenter-а адаптера дворника.
     * Шаги:
     * 1) Возвращает class-string `WiperAdapterDetailsData`.
     *
     * @return class-string<WiperAdapterDetailsData>
     */
    protected function dataClass(): string
    {
        return WiperAdapterDetailsData::class;
    }

    /**
     * Этот метод рендерит details адаптера дворника в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `WiperAdapterDetailsData`.
     * 2) Переводит расположение и multi-select креплений в labels.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, WiperAdapterDetailsData::class);

        return [
            $this->nameToLabelCell(PositionEnum::class, $data->position),
            $this->namesToLabelString($data->adapterTypeFront, FrontAdapterTypeEnum::class),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
