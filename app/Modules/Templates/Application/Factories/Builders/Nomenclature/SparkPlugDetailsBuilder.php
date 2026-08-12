<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeSideCountEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\SparkPlugDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\SparkPlugElectrodeDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\SparkPlugThreadDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TighteningTorqueData;

/**
 * Строит форму шаблона `sparkPlugs` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `SparkPlugDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class SparkPlugDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    /**
     * Этот метод собирает nomenclature spark-plug details из Excel-строки.
     * Шаги:
     * 1) Собирает вложенные блоки резьбы и электрода.
     * 2) Читает ширину зева ключа и момент затяжки.
     * 3) Читает общий блок габаритов номенклатуры.
     * 4) Возвращает `SparkPlugDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): SparkPlugDetailsData
    {
        return new SparkPlugDetailsData(
            thread: $this->buildThread($cursor),
            electrode: $this->buildElectrode($cursor),
            wrenchJawWidth: $cursor->pullRequiredLabel(WrenchJawWidthEnum::class, 'Ширина зева гаечного ключа')->name,
            tighteningTorque: $this->buildTighteningTorque($cursor),
            metrics: $this->buildMetrics($cursor),
        );
    }

    /**
     * Этот метод собирает блок резьбы номенклатурной свечи.
     * Шаги:
     * 1) Читает размер, шаг и длину резьбы через enum-справочники.
     * 2) Возвращает `SparkPlugThreadDetailsData` с enum-name значениями.
     */
    private function buildThread(DetailsRowCursor $cursor): SparkPlugThreadDetailsData
    {
        return new SparkPlugThreadDetailsData(
            size: $cursor->pullRequiredLabel(ThreadSizeEnum::class, 'Размер резьбы')->name,
            pitch: $cursor->pullRequiredLabel(ThreadPitchEnum::class, 'Шаг резьбы')->name,
            length: $cursor->pullRequiredLabel(ThreadLengthEnum::class, 'Длина резьбы')->name,
        );
    }

    /**
     * Этот метод собирает блок электрода номенклатурной свечи.
     * Шаги:
     * 1) Читает межконтактный зазор через `ElectrodeGapEnum`.
     * 2) Читает число боковых электродов через `ElectrodeSideCountEnum`.
     * 3) Возвращает `SparkPlugElectrodeDetailsData`.
     */
    private function buildElectrode(DetailsRowCursor $cursor): SparkPlugElectrodeDetailsData
    {
        return new SparkPlugElectrodeDetailsData(
            gap: $cursor->pullRequiredLabel(ElectrodeGapEnum::class, 'Межконтактный зазор')->name,
            countSide: $cursor->pullRequiredLabel(ElectrodeSideCountEnum::class, 'Число боковых электродов')->name,
        );
    }

    /**
     * Этот метод собирает диапазон момента затяжки свечи.
     * Шаги:
     * 1) Читает минимальный момент затяжки.
     * 2) Читает максимальный момент затяжки.
     * 3) Возвращает `TighteningTorqueData`.
     */
    private function buildTighteningTorque(DetailsRowCursor $cursor): TighteningTorqueData
    {
        return new TighteningTorqueData(
            min: $cursor->pullRequiredFloatCell('Минимальный момент затяжки'),
            max: $cursor->pullRequiredFloatCell('Максимальный момент затяжки'),
        );
    }
}
