<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories;

use App\Modules\Templates\Application\Factories\Builders\AirFilterDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\OilFilterDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\SparkPlugDetailsBuilder;
use App\Modules\Templates\Application\Factories\Builders\WiperDetailsBuilder;
use App\Modules\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Selector: по `DetailTemplateEnum` выбирает билдер конкретного шаблона (`Builders/*`, один
 * класс на шаблон — Wiper/SparkPlug/OilFilter/AirFilter больше не смешаны в одном файле, как
 * раньше в `buildWiper()`/`buildSparkPlugs()`/... здесь же) и заводит курсор чтения строки.
 * Билдеры — простые классы без собственного порта (не резолвятся из контейнера независимо,
 * вызываются только отсюда, подмена не нужна) — конструируются по умолчанию, как уже делает сам
 * `DetailsRowCursor`.
 */
final readonly class DetailsDataFactory implements DetailsDataFactoryInterface
{
    /**
     * Этот конструктор принимает набор билдеров автомобильных details-шаблонов.
     * Шаги:
     * 1) Сохраняет билдеры в readonly-поля.
     * 2) Использует дефолтные инстансы для обычного runtime-сценария, где factory создаётся без
     *    явной конфигурации контейнера.
     */
    public function __construct(
        private WiperDetailsBuilder $wiper = new WiperDetailsBuilder,
        private SparkPlugDetailsBuilder $sparkPlugs = new SparkPlugDetailsBuilder,
        private OilFilterDetailsBuilder $oilFilter = new OilFilterDetailsBuilder,
        private AirFilterDetailsBuilder $airFilter = new AirFilterDetailsBuilder,
    ) {}

    /**
     * Этот метод строит details конкретного шаблона из Excel-строки и отдаёт типизированный
     * объект (не `array`) — превращать его в массив (`->toArray()`) перед записью в
     * `PartSpecificationData::$details` решает уже вызывающий код.
     * Шаги:
     * 1) Заводит курсор чтения строки, начиная с переданной позиции.
     * 2) По `match` вызывает билдер, соответствующий шаблону.
     * 3) Синхронизирует внешнюю ссылку `&$index` с итоговой позицией курсора.
     * 4) Возвращает собранный типизированный объект.
     */
    public function make(DetailTemplateEnum $template, array $row, int &$index): AbstractDetailsData
    {
        $cursor = new DetailsRowCursor($row, $index);

        $data = match ($template) {
            DetailTemplateEnum::WIPER => $this->wiper->build($cursor),
            DetailTemplateEnum::SPARK_PLUGS => $this->sparkPlugs->build($cursor),
            DetailTemplateEnum::OIL_FILTER => $this->oilFilter->build($cursor),
            DetailTemplateEnum::AIR_FILTER => $this->airFilter->build($cursor),
        };

        $index = $cursor->position();

        return $data;
    }
}
