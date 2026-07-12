<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Templates\Domain\Enums\SparkPlug\ElectrodeSideCountEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Templates\Domain\ModelData\Nomenclature\SparkPlugDetailsData;
use App\Templates\Domain\ModelData\Nomenclature\SparkPlugElectrodeDetailsData;
use App\Templates\Domain\ModelData\Nomenclature\SparkPlugThreadDetailsData;
use App\Templates\Domain\ModelData\Nomenclature\TighteningTorqueData;

/**
 * Строит форму шаблона `sparkPlugs` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `SparkPlugDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class SparkPlugDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): SparkPlugDetailsData
    {
        return new SparkPlugDetailsData(
            thread: $this->buildThread($cursor),
            electrode: $this->buildElectrode($cursor),
            wrenchJawWidth: $cursor->pullLabel(WrenchJawWidthEnum::class)?->name,
            tighteningTorque: $this->buildTighteningTorque($cursor),
            metrics: $this->buildMetrics($cursor),
        );
    }

    private function buildThread(DetailsRowCursor $cursor): SparkPlugThreadDetailsData
    {
        return new SparkPlugThreadDetailsData(
            size: $cursor->pullLabel(ThreadSizeEnum::class)?->name,
            pitch: $cursor->pullLabel(ThreadPitchEnum::class)?->name,
            length: $cursor->pullLabel(ThreadLengthEnum::class)?->name,
        );
    }

    private function buildElectrode(DetailsRowCursor $cursor): SparkPlugElectrodeDetailsData
    {
        return new SparkPlugElectrodeDetailsData(
            gap: $cursor->pullLabel(ElectrodeGapEnum::class)?->name,
            countSide: $cursor->pullLabel(ElectrodeSideCountEnum::class)?->name,
        );
    }

    private function buildTighteningTorque(DetailsRowCursor $cursor): TighteningTorqueData
    {
        return new TighteningTorqueData(
            min: $cursor->pullFloatCell(),
            max: $cursor->pullFloatCell(),
        );
    }
}
