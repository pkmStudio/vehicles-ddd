<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application\Factories;

use App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface;
use App\Vehicles\Templates\Domain\Contracts\Factories\DetailsDataPresenterInterface;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Templates\Domain\Enums\Filter\FormEnum;
use App\Vehicles\Templates\Domain\Enums\Filter\OilFilterFatherEnum;
use App\Vehicles\Templates\Domain\Enums\Filter\OilFilterThreadEnum;
use App\Vehicles\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Vehicles\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Vehicles\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Vehicles\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Vehicles\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Vehicles\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Vehicles\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Vehicles\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Vehicles\Templates\Domain\ModelData\Engine\AirFilterDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Engine\OilFilterDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Engine\OilFilterMetricsData;
use App\Vehicles\Templates\Domain\ModelData\Engine\SparkPlugDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Engine\SparkPlugElectrodeDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Engine\SparkPlugThreadDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperBackDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperFrontDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperLengthRangeData;

/**
 * Рендерит details конкретного шаблона в плоский набор Excel-ячеек экспорта (обратная сторона
 * `DetailsDataFactory`). Раньше эта логика (плюс диспетчеризация по `DetailTemplateEnum`) лежала
 * на самих `<X>DetailsData::toExportCells()`/`::headings()` (через трейт `DetailsRowIO`) — теперь
 * это один инъектируемый Application-сервис, симметричный `DetailsDataFactory`: `<X>DetailsData`
 * остаются чистыми объектами-значениями (только поля), вся знание "какая колонка что значит" —
 * здесь и в фабрике, а не размазано по 8 классам.
 */
final readonly class DetailsDataPresenter implements DetailsDataPresenterInterface
{
    /**
     * Этот метод отдаёт полный список заголовков Excel-колонок для конкретного шаблона.
     * Шаги:
     * 1) По `match` вызывает приватный сборщик заголовков, соответствующий шаблону.
     */
    public function headingsFor(DetailTemplateEnum $template): array
    {
        return match ($template) {
            DetailTemplateEnum::WIPER => $this->wiperHeadings(),
            DetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugsHeadings(),
            DetailTemplateEnum::OIL_FILTER => $this->oilFilterHeadings(),
            DetailTemplateEnum::AIR_FILTER => $this->airFilterHeadings(),
        };
    }

    /**
     * Этот метод рендерит details конкретного шаблона в плоский набор Excel-ячеек экспорта.
     * Шаги:
     * 1) По `match` строит типизированный `<X>DetailsData` из сохранённого plain-массива
     *    (`::from()` — стандартный механизм spatie/laravel-data, не наша логика).
     * 2) Вызывает приватный рендерер, соответствующий шаблону.
     */
    public function toExportCells(DetailTemplateEnum $template, array $details): array
    {
        return match ($template) {
            DetailTemplateEnum::WIPER => $this->wiperCells(WiperDetailsData::from($details)),
            DetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugsCells(SparkPlugDetailsData::from($details)),
            DetailTemplateEnum::OIL_FILTER => $this->oilFilterCells(OilFilterDetailsData::from($details)),
            DetailTemplateEnum::AIR_FILTER => $this->airFilterCells(AirFilterDetailsData::from($details)),
        };
    }

    private function wiperHeadings(): array
    {
        return [...$this->wiperFrontHeadings(), ...$this->wiperBackHeadings()];
    }

    private function wiperFrontHeadings(): array
    {
        return [
            'Размеры водительской щетки в мм От',
            'Размеры водительской щетки в мм До',
            'Размеры пассажирской щетки в мм От',
            'Размеры пассажирской щетки в мм До',
            'Тип крепления передних',
            'Количество передних щеток',
        ];
    }

    private function wiperBackHeadings(): array
    {
        return [
            'Размеры задней щетки в мм От',
            'Размеры задней щетки в мм До',
            'Тип крепления задней',
            'Количество задних щеток',
        ];
    }

    /**
     * Этот метод рендерит значения дворников (обе стороны) как плоский набор Excel-ячеек.
     * Шаги:
     * 1) Берёт переднюю сторону, если она есть; если её нет (в смерженном
     *    `WiperSpecificationService::mergeForExport()` массиве не было ключа `front`) —
     *    использует пустой объект вместо неё, чтобы количество ячеек не поменялось.
     * 2) То же самое для задней стороны.
     * 3) Разворачивает ячейки обеих сторон подряд в один плоский список.
     */
    private function wiperCells(WiperDetailsData $data): array
    {
        return [
            ...$this->wiperFrontCells($data->front ?? new WiperFrontDetailsData),
            ...$this->wiperBackCells($data->back ?? new WiperBackDetailsData),
        ];
    }

    private function wiperFrontCells(WiperFrontDetailsData $front): array
    {
        return [
            ...$this->lengthRangeCells($front->lengthMain),
            ...$this->lengthRangeCells($front->lengthSecond),
            $this->namesToLabelString($front->adapterTypeFront, FrontAdapterTypeEnum::class),
            $front->countWipers,
        ];
    }

    private function wiperBackCells(WiperBackDetailsData $back): array
    {
        return [
            ...$this->lengthRangeCells($back->lengthRear),
            $this->namesToLabelString($back->adapterTypeRear, RearAdapterTypeEnum::class),
            $back->countWipers,
        ];
    }

    /** @return array{0: ?int, 1: ?int} */
    private function lengthRangeCells(WiperLengthRangeData $range): array
    {
        return [$range->min, $range->max];
    }

    private function sparkPlugsHeadings(): array
    {
        return [
            ...$this->sparkPlugThreadHeadings(),
            ...$this->sparkPlugElectrodeHeadings(),
            'Ширина зева гаечного ключа (мм)',
        ];
    }

    private function sparkPlugThreadHeadings(): array
    {
        return ['Размер резьбы', 'Шаг резьбы (мм)', 'Длина резьбы (мм)'];
    }

    private function sparkPlugElectrodeHeadings(): array
    {
        return ['Межконтактный зазор (мм)'];
    }

    private function sparkPlugsCells(SparkPlugDetailsData $data): array
    {
        return [
            ...$this->sparkPlugThreadCells($data->thread),
            ...$this->sparkPlugElectrodeCells($data->electrode),
            $this->nameToLabelCell(WrenchJawWidthEnum::class, $data->wrenchJawWidth),
        ];
    }

    private function sparkPlugThreadCells(SparkPlugThreadDetailsData $thread): array
    {
        return [
            $this->nameToLabelCell(ThreadSizeEnum::class, $thread->size),
            $this->nameToLabelCell(ThreadPitchEnum::class, $thread->pitch),
            $this->nameToLabelCell(ThreadLengthEnum::class, $thread->length),
        ];
    }

    private function sparkPlugElectrodeCells(SparkPlugElectrodeDetailsData $electrode): array
    {
        return [$this->nameToLabelCell(ElectrodeGapEnum::class, $electrode->gap)];
    }

    private function oilFilterHeadings(): array
    {
        return [
            'Исполнение фильтра',
            'Форма фильтра',
            'Резьба или Папа',
            'Диаметр (мм)',
            'Диаметр внешний уплотнителя (мм) или мама',
            ...$this->oilFilterMetricsHeadings(),
        ];
    }

    private function oilFilterMetricsHeadings(): array
    {
        return ['Длина (мм)', 'Ширина (мм)', 'Высота (мм)'];
    }

    /**
     * Этот метод рендерит масляный фильтр (не подключён ни к одному Import/Export сценарию —
     * см. докблок `OilFilterDetailsData`, портируется без покрытия тестами).
     * Шаги:
     * 1) Переводит исполнение и форму фильтра обратно в лейблы.
     * 2) Переводит `father` обратно в лейбл через `oilFilterFatherToLabel()` — пробуя два
     *    словаря по очереди, симметрично `DetailsDataFactory::pullOilFilterFather()`.
     * 3) Добавляет диаметр и диаметр уплотнителя как есть.
     * 4) Добавляет ячейки вложенных размеров.
     */
    private function oilFilterCells(OilFilterDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(PerformanceEnum::class, $data->performance),
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->oilFilterFatherToLabel($data->father),
            $data->diameter,
            $data->mother,
            ...$this->oilFilterMetricsCells($data->metrics),
        ];
    }

    private function oilFilterMetricsCells(OilFilterMetricsData $metrics): array
    {
        return [
            $this->floatArrayToString($metrics->length),
            $this->floatArrayToString($metrics->width),
            $this->floatArrayToString($metrics->height),
        ];
    }

    /**
     * Этот метод переводит хранимое имя `father` обратно в Excel-лейбл, пробуя два словаря по
     * очереди (симметрично `DetailsDataFactory::pullOilFilterFather()`).
     * Шаги:
     * 1) Если имя null — возвращает пустую строку (ячейка остаётся пустой).
     * 2) Иначе сначала пробует резолвить имя как case `OilFilterThreadEnum`.
     * 3) Если не нашёл — пробует `OilFilterFatherEnum`.
     * 4) Возвращает лейбл найденного case'а (или пустую строку, если имя не резолвилось ни в
     *    одном из двух словарей).
     */
    private function oilFilterFatherToLabel(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        return (OilFilterThreadEnum::fromName($name) ?? OilFilterFatherEnum::fromName($name))?->value ?? '';
    }

    private function airFilterHeadings(): array
    {
        return ['Форма фильтра', 'Длина (мм)', 'Ширина (мм)', 'Высота (мм)', 'Диаметр (мм)'];
    }

    private function airFilterCells(AirFilterDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->floatArrayToString($data->length),
            $this->floatArrayToString($data->width),
            $this->floatArrayToString($data->height),
            $data->diameter,
        ];
    }

    /**
     * Этот метод переводит хранимое имя case'а enum'а (`->name`) обратно в Excel-лейбл.
     * Шаги:
     * 1) Если имя null — возвращает null (писать в ячейку нечего).
     * 2) Иначе резолвит case по имени через `$enumClass::fromName()`.
     * 3) Возвращает `->value` найденного case'а (или null, если имя не резолвилось ни во что).
     *
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    private function nameToLabel(string $enumClass, ?string $name): ?string
    {
        return $name === null ? null : $enumClass::fromName($name)?->value;
    }

    /**
     * Этот метод делает то же самое, что `nameToLabel()`, но для одиночной Excel-ячейки, где
     * отсутствующее значение должно быть пустой строкой, а не null.
     * Шаги:
     * 1) Зовёт `nameToLabel()` для реального перевода имени в лейбл.
     * 2) Если результат null (имя пустое или не резолвилось) — подставляет пустую строку.
     *    (Воспроизводит старый `ExportDetailsBuilder::getVarValue()`, который всегда
     *    `implode(';', ...)`, а не null, для select/conditional_select-полей.)
     *
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    private function nameToLabelCell(string $enumClass, ?string $name): string
    {
        return $this->nameToLabel($enumClass, $name) ?? '';
    }

    /**
     * Этот метод делает обратную операцию к `DetailsRowCursor::pullMultiLabel()` — превращает
     * массив хранимых имён в `;`-джойн строку Excel-лейблов.
     * Шаги:
     * 1) Для каждого имени в массиве резолвит лейбл через `nameToLabel()`.
     * 2) Пропускает имена, которые не удалось перевести в лейбл (null).
     * 3) Склеивает собранные лейблы через `;` и возвращает готовую строку для ячейки.
     *
     * @param  array<int, string>  $names
     * @param  class-string<EnumHelperInterface>  $enumClass
     */
    private function namesToLabelString(array $names, string $enumClass): string
    {
        $labels = [];
        foreach ($names as $name) {
            $label = $this->nameToLabel($enumClass, $name);
            if ($label !== null) {
                $labels[] = $label;
            }
        }

        return implode(';', $labels);
    }

    /**
     * Этот метод склеивает массив чисел в `;`-джойн строку для Excel-ячейки.
     * Шаги:
     * 1) Принимает готовый массив `float`-значений.
     * 2) Склеивает их через `;` и возвращает строку (пустой массив даёт пустую строку).
     *
     * @param  array<int, float>  $values
     */
    private function floatArrayToString(array $values): string
    {
        return implode(';', $values);
    }
}
