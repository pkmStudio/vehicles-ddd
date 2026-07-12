<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application\Factories;

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
use App\Vehicles\Templates\Domain\ModelData\AbstractDetailsData;
use App\Vehicles\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;

/**
 * Собирает details конкретного шаблона из Excel-строки. Раньше эта сборка (плюс диспетчеризация
 * по `DetailTemplateEnum`) частями лежала то на самих `<X>DetailsData::fromImportRow()`
 * (статика — обязана была быть, т.к. вызывалась до появления инстанса), то прямо на enum'е
 * (`DetailTemplateEnum::buildDetailsFromRow()` — поведение на «словаре» `Shared`, где ему не
 * место). Теперь это один инъектируемый Application-сервис: `<X>DetailsData` остаются чистыми
 * объектами-значениями (только поля, без единого метода — обратное направление, рендер в
 * Excel-ячейки, см. `DetailsDataPresenter`), вся сборка — обычные (не статичные) методы этого
 * класса, т.к. `$this` есть с самого начала.
 */
final readonly class DetailsDataFactory implements DetailsDataFactoryInterface
{
    /**
     * Этот метод строит details конкретного шаблона из Excel-строки и отдаёт типизированный
     * объект (не `array`) — превращать его в массив (`->toArray()`) перед записью в
     * `PartSpecificationData::$details` решает уже вызывающий код.
     * Шаги:
     * 1) Заводит курсор чтения строки, начиная с переданной позиции.
     * 2) По `match` вызывает приватный сборщик, соответствующий шаблону.
     * 3) Синхронизирует внешнюю ссылку `&$index` с итоговой позицией курсора.
     * 4) Возвращает собранный типизированный объект.
     */
    public function buildFromRow(DetailTemplateEnum $template, array $row, int &$index): AbstractDetailsData
    {
        $cursor = new DetailsRowCursor($row, $index);

        $data = match ($template) {
            DetailTemplateEnum::WIPER => $this->buildWiper($cursor),
            DetailTemplateEnum::SPARK_PLUGS => $this->buildSparkPlugs($cursor),
            DetailTemplateEnum::OIL_FILTER => $this->buildOilFilter($cursor),
            DetailTemplateEnum::AIR_FILTER => $this->buildAirFilter($cursor),
        };

        $index = $cursor->position();

        return $data;
    }

    /**
     * Этот метод строит форму дворников (обе стороны).
     * Шаги:
     * 1) Строит переднюю сторону.
     * 2) Строит заднюю сторону.
     * 3) Собирает обе стороны в один объект.
     */
    private function buildWiper(DetailsRowCursor $cursor): WiperDetailsData
    {
        return new WiperDetailsData(
            front: $this->buildWiperFront($cursor),
            back: $this->buildWiperBack($cursor),
        );
    }

    /**
     * Этот метод строит параметры передних щёток, читая 6 ячеек подряд из курсора.
     * Шаги:
     * 1) Читает диапазон `length_main`.
     * 2) Читает диапазон `length_second`.
     * 3) Читает тип крепления (курсор сам переводит `;`-джойн лейблы в имена).
     * 4) Читает количество щёток и собирает объект.
     */
    private function buildWiperFront(DetailsRowCursor $cursor): WiperFrontDetailsData
    {
        return new WiperFrontDetailsData(
            lengthMain: $this->buildLengthRange($cursor),
            lengthSecond: $this->buildLengthRange($cursor),
            adapterTypeFront: $this->namesOf($cursor->pullMultiLabel(FrontAdapterTypeEnum::class)),
            countWipers: $cursor->pullIntCell(),
        );
    }

    /**
     * Этот метод строит параметры задних щёток, читая 4 ячейки подряд из курсора.
     * Шаги:
     * 1) Читает диапазон `length_rear`.
     * 2) Читает тип крепления (курсор сам переводит `;`-джойн лейблы в имена).
     * 3) Читает количество щёток и собирает объект.
     */
    private function buildWiperBack(DetailsRowCursor $cursor): WiperBackDetailsData
    {
        return new WiperBackDetailsData(
            lengthRear: $this->buildLengthRange($cursor),
            adapterTypeRear: $this->namesOf($cursor->pullMultiLabel(RearAdapterTypeEnum::class)),
            countWipers: $cursor->pullIntCell(),
        );
    }

    /**
     * Этот метод превращает массив резолвнутых case'ов в массив их хранимых имён (`->name`).
     * Шаги:
     * 1) Проходит по каждому case'у в массиве.
     * 2) Достаёт `->name` и собирает в новый массив — это то, что реально кладётся в поле
     *    `Data`-класса и, дальше, в details JSON.
     *
     * @param  array<int, \App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface>  $cases
     * @return array<int, string>
     */
    private function namesOf(array $cases): array
    {
        return array_map(static fn ($case) => $case->name, $cases);
    }

    /**
     * Этот метод строит диапазон длины, читая 2 ячейки (min, max) из курсора.
     * Шаги:
     * 1) Читает ячейку `min`.
     * 2) Читает ячейку `max` и собирает объект.
     */
    private function buildLengthRange(DetailsRowCursor $cursor): WiperLengthRangeData
    {
        return new WiperLengthRangeData(
            min: $cursor->pullIntCell(),
            max: $cursor->pullIntCell(),
        );
    }

    /**
     * Этот метод строит форму свечи зажигания.
     * Шаги:
     * 1) Строит резьбу.
     * 2) Строит электрод.
     * 3) Читает ширину зева ключа и собирает объект.
     */
    private function buildSparkPlugs(DetailsRowCursor $cursor): SparkPlugDetailsData
    {
        return new SparkPlugDetailsData(
            thread: $this->buildSparkPlugThread($cursor),
            electrode: $this->buildSparkPlugElectrode($cursor),
            wrenchJawWidth: $cursor->pullLabel(WrenchJawWidthEnum::class)?->name,
        );
    }

    /**
     * Этот метод строит резьбу свечи, читая 3 ячейки подряд из курсора.
     * Шаги:
     * 1) Читает размер резьбы.
     * 2) Читает шаг резьбы.
     * 3) Читает длину резьбы и собирает объект.
     */
    private function buildSparkPlugThread(DetailsRowCursor $cursor): SparkPlugThreadDetailsData
    {
        return new SparkPlugThreadDetailsData(
            size: $cursor->pullLabel(ThreadSizeEnum::class)?->name,
            pitch: $cursor->pullLabel(ThreadPitchEnum::class)?->name,
            length: $cursor->pullLabel(ThreadLengthEnum::class)?->name,
        );
    }

    /**
     * Этот метод строит электрод свечи, читая 1 ячейку из курсора.
     * Шаги:
     * 1) Читает межконтактный зазор и собирает объект.
     */
    private function buildSparkPlugElectrode(DetailsRowCursor $cursor): SparkPlugElectrodeDetailsData
    {
        return new SparkPlugElectrodeDetailsData(
            gap: $cursor->pullLabel(ElectrodeGapEnum::class)?->name,
        );
    }

    /**
     * Этот метод строит форму масляного фильтра (не подключена ни к одному Import/Export
     * сценарию — см. докблок `OilFilterDetailsData`, портируется декларативно без покрытия
     * тестами).
     * Шаги:
     * 1) Читает исполнение и форму фильтра.
     * 2) Читает `father` через `pullOilFilterFather()` — независимо от `performance` (старое
     *    поведение DSL, зависимость проверялась только в Filament UI, не в парсинге).
     * 3) Читает диаметр и диаметр уплотнителя.
     * 4) Строит вложенные размеры и собирает объект.
     */
    private function buildOilFilter(DetailsRowCursor $cursor): OilFilterDetailsData
    {
        return new OilFilterDetailsData(
            performance: $cursor->pullLabel(PerformanceEnum::class)?->name,
            form: $cursor->pullLabel(FormEnum::class)?->name,
            father: $this->pullOilFilterFather($cursor),
            diameter: $cursor->pullIntCell(),
            mother: $cursor->pullIntCell(),
            metrics: $this->buildOilFilterMetrics($cursor),
        );
    }

    /**
     * Этот метод строит размеры масляного фильтра, читая 3 ячейки подряд из курсора.
     * Шаги:
     * 1) Читает список длины.
     * 2) Читает список ширины.
     * 3) Читает список высоты и собирает объект.
     */
    private function buildOilFilterMetrics(DetailsRowCursor $cursor): OilFilterMetricsData
    {
        return new OilFilterMetricsData(
            length: $cursor->pullFloatArray(),
            width: $cursor->pullFloatArray(),
            height: $cursor->pullFloatArray(),
        );
    }

    /**
     * Этот метод читает `father`, пробуя два словаря по очереди (см. докблок класса —
     * зависимость от `performance` в старом коде не проверялась).
     * Шаги:
     * 1) Читает сырую ячейку через курсор.
     * 2) Если ячейка пустая — возвращает null.
     * 3) Иначе сначала пробует найти лейбл в `OilFilterThreadEnum`.
     * 4) Если не нашёл — пробует `OilFilterFatherEnum`; возвращает хранимое имя найденного case'а
     *    (или null, если не нашёлся ни в одном из двух словарей).
     */
    private function pullOilFilterFather(DetailsRowCursor $cursor): ?string
    {
        $label = $cursor->pullCell();
        if ($label === null) {
            return null;
        }

        return (OilFilterThreadEnum::fromLabel((string) $label) ?? OilFilterFatherEnum::fromLabel((string) $label))?->name;
    }

    /**
     * Этот метод строит форму воздушного фильтра (не подключена ни к одному Import/Export
     * сценарию — см. докблок `AirFilterDetailsData`, портируется декларативно без покрытия
     * тестами).
     * Шаги:
     * 1) Читает форму фильтра.
     * 2) Читает списки длины/ширины/высоты.
     * 3) Читает диаметр и собирает объект.
     */
    private function buildAirFilter(DetailsRowCursor $cursor): AirFilterDetailsData
    {
        return new AirFilterDetailsData(
            form: $cursor->pullLabel(FormEnum::class)?->name,
            length: $cursor->pullFloatArray(),
            width: $cursor->pullFloatArray(),
            height: $cursor->pullFloatArray(),
            diameter: $cursor->pullFloatCell(),
        );
    }
}
