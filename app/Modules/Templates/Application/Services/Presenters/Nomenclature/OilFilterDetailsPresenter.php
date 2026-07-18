<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\OilFilterDetailsData;

/** Рендерит форму `oilFilter` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class OilFilterDetailsPresenter
{
    use RendersNomenclatureMetrics;

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

    public function cells(OilFilterDetailsData $data): array
    {
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

    /** Симметрично `OilFilterDetailsBuilder::pullFather()` — пробует оба словаря по очереди. */
    private function fatherCell(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        return (OilFilterThreadEnum::fromName($name) ?? OilFilterFatherEnum::fromName($name))?->value ?? '';
    }
}
