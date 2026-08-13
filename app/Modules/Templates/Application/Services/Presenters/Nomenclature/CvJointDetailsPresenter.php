<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\CvJointDetailsData;

/** Рендерит форму `cvJoint` (Nomenclature, ШРУС) в плоский набор Excel-ячеек экспорта. */
final readonly class CvJointDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки ШРУС.
     * Шаги:
     * 1) Перечисляет резьбу, две длины, ABS, диаметр под сальник и шлицы.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return [
            'Резьба 1', 'Длина 1 (мм)', 'Длина 2 (мм)', 'ABS', 'Диаметр под сальник (мм)',
            'Шлицы наружные, шт.', 'Шлицы внутренние, шт.',
            ...$this->metricsHeadings(),
        ];
    }

    /**
     * Этот метод указывает Data-класс presenter-а ШРУС.
     * Шаги:
     * 1) Возвращает class-string `CvJointDetailsData`.
     *
     * @return class-string<CvJointDetailsData>
     */
    protected function dataClass(): string
    {
        return CvJointDetailsData::class;
    }

    /**
     * Этот метод рендерит details ШРУС в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `CvJointDetailsData`.
     * 2) Выводит scalar-поля без справочных преобразований.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, CvJointDetailsData::class);

        return [
            $data->thread1,
            $data->length1,
            $data->length2,
            $data->abs,
            $data->sealDiameter,
            $data->splinesOuter,
            $data->splinesInner,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
