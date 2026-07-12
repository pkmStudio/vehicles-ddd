<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Templates\Domain\ModelData\Engine\SparkPlugDetailsData;
use App\Templates\Domain\ModelData\Engine\SparkPlugElectrodeDetailsData;
use App\Templates\Domain\ModelData\Engine\SparkPlugThreadDetailsData;

/**
 * Строит форму шаблона `sparkPlugs` из Excel-строки. Выделено из `DetailsDataFactory`. Простой
 * класс без собственного порта — вызывается только из `DetailsDataFactory`, подмена не нужна.
 */
final readonly class SparkPlugDetailsBuilder
{
    public function build(DetailsRowCursor $cursor): SparkPlugDetailsData
    {
        return new SparkPlugDetailsData(
            thread: $this->buildThread($cursor),
            electrode: $this->buildElectrode($cursor),
            wrenchJawWidth: $cursor->pullLabel(WrenchJawWidthEnum::class)?->name,
        );
    }

    /** Читает 3 ячейки подряд: размер резьбы, шаг резьбы, длина резьбы. */
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
        );
    }
}
