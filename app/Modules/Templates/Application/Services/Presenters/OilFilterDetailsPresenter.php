<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters;

use App\Modules\Templates\Application\Traits\FormatsExportCells;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\Engine\OilFilterDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\OilFilterMetricsData;

/**
 * Рендерит форму `oilFilter` в плоский набор Excel-ячеек экспорта (не подключена ни к одному
 * Import/Export сценарию — см. докблок `OilFilterDetailsData`, портируется без покрытия
 * тестами). Выделено из `DetailsDataPresenter`, чтобы неподключённая логика не лежала в одном
 * файле с рабочей (Wiper/SparkPlug). Простой класс без собственного порта.
 */
final readonly class OilFilterDetailsPresenter
{
    use FormatsExportCells;

    public function headings(): array
    {
        return [
            'Исполнение фильтра',
            'Форма фильтра',
            'Резьба или Папа',
            'Диаметр (мм)',
            'Диаметр внешний уплотнителя (мм) или мама',
            ...$this->metricsHeadings(),
        ];
    }

    private function metricsHeadings(): array
    {
        return ['Длина (мм)', 'Ширина (мм)', 'Высота (мм)'];
    }

    /**
     * Шаги:
     * 1) Переводит исполнение и форму фильтра обратно в лейблы.
     * 2) Переводит `father` обратно в лейбл через `fatherToLabel()` — пробуя два словаря по
     *    очереди, симметрично `OilFilterDetailsBuilder::pullFather()`.
     * 3) Добавляет диаметр и диаметр уплотнителя как есть.
     * 4) Добавляет ячейки вложенных размеров.
     */
    public function cells(OilFilterDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(PerformanceEnum::class, $data->performance),
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->fatherToLabel($data->father),
            $data->diameter,
            $data->mother,
            ...$this->metricsCells($data->metrics),
        ];
    }

    private function metricsCells(OilFilterMetricsData $metrics): array
    {
        return [
            $this->floatArrayToString($metrics->length),
            $this->floatArrayToString($metrics->width),
            $this->floatArrayToString($metrics->height),
        ];
    }

    /**
     * Этот метод переводит хранимое имя `father` обратно в Excel-лейбл, пробуя два словаря по
     * очереди (симметрично `OilFilterDetailsBuilder::pullFather()`).
     * Шаги:
     * 1) Если имя null — возвращает пустую строку (ячейка остаётся пустой).
     * 2) Иначе сначала пробует резолвить имя как case `OilFilterThreadEnum`.
     * 3) Если не нашёл — пробует `OilFilterFatherEnum`.
     * 4) Возвращает лейбл найденного case'а (или пустую строку, если имя не резолвилось ни в
     *    одном из двух словарей).
     */
    private function fatherToLabel(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        return (OilFilterThreadEnum::fromName($name) ?? OilFilterFatherEnum::fromName($name))?->value ?? '';
    }
}
