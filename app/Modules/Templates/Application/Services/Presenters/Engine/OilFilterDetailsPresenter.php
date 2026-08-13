<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Engine;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\FormatsExportCells;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Modules\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\Exceptions\UnknownEnumValueException;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\OilFilterDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\OilFilterMetricsData;

/**
 * Рендерит форму `oilFilter` в плоский набор Excel-ячеек экспорта (не подключена ни к одному
 * Import/Export сценарию — см. докблок `OilFilterDetailsData`, портируется без покрытия
 * тестами). Выделено из `DetailsDataPresenter`, чтобы неподключённая логика не лежала в одном
 * файле с рабочей (Wiper/SparkPlug). Простой класс без собственного порта.
 */
final readonly class OilFilterDetailsPresenter extends AbstractDetailsPresenter
{
    use FormatsExportCells;

    /**
     * Этот метод возвращает колонки engine oil-filter шаблона.
     * Шаги:
     * 1) Перечисляет select-поля исполнения/формы/father и числовые диаметры.
     * 2) Добавляет заголовки вложенного metrics-блока длина/ширина/высота.
     */
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

    /**
     * Этот метод возвращает заголовки вложенных габаритов масляного фильтра.
     * Шаги:
     * 1) Перечисляет длину, ширину и высоту в порядке `metricsCells()`.
     */
    private function metricsHeadings(): array
    {
        return ['Длина (мм)', 'Ширина (мм)', 'Высота (мм)'];
    }

    /**
     * Этот метод указывает Data-класс engine oil-filter presenter-а.
     * Шаги:
     * 1) Возвращает class-string `OilFilterDetailsData` для восстановления details массива.
     *
     * @return class-string<OilFilterDetailsData>
     */
    protected function dataClass(): string
    {
        return OilFilterDetailsData::class;
    }

    /**
     * Этот метод рендерит engine oil-filter details в Excel-ячейки.
     * Шаги:
     * 1) Проверяет, что получен `OilFilterDetailsData`.
     * 2) Переводит исполнение и форму фильтра обратно в labels.
     * 3) Переводит `father` через два словаря и добавляет числовые диаметры.
     * 4) Разворачивает вложенный metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, OilFilterDetailsData::class);

        return [
            $this->nameToLabelCell(PerformanceEnum::class, $data->performance),
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->fatherToLabel($data->father),
            $data->diameter,
            $data->mother,
            ...$this->metricsCells($data->metrics),
        ];
    }

    /**
     * Этот метод рендерит вложенные габариты масляного фильтра.
     * Шаги:
     * 1) Склеивает списки длины, ширины и высоты через `;`.
     * 2) Возвращает три ячейки в порядке заголовков.
     */
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

        $case = OilFilterThreadEnum::fromName($name) ?? OilFilterFatherEnum::fromName($name);

        if ($case === null) {
            throw UnknownEnumValueException::name('резьбы/папы масляного фильтра', $name);
        }

        return $case->value;
    }
}
