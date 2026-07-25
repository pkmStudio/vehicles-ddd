<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugElectrodeDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugThreadDetailsData;

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
            wrenchJawWidth: $cursor->pullRequiredLabel(WrenchJawWidthEnum::class, 'Ширина зева гаечного ключа')->name,
        );
    }

    /** Читает 3 ячейки подряд: размер резьбы, шаг резьбы, длина резьбы. */
    private function buildThread(DetailsRowCursor $cursor): SparkPlugThreadDetailsData
    {
        return new SparkPlugThreadDetailsData(
            size: $cursor->pullRequiredLabel(ThreadSizeEnum::class, 'Размер резьбы')->name,
            pitch: $cursor->pullRequiredLabel(ThreadPitchEnum::class, 'Шаг резьбы')->name,
            length: $cursor->pullRequiredLabel(ThreadLengthEnum::class, 'Длина резьбы')->name,
        );
    }

    private function buildElectrode(DetailsRowCursor $cursor): SparkPlugElectrodeDetailsData
    {
        return new SparkPlugElectrodeDetailsData(
            gap: $cursor->pullRequiredLabel(ElectrodeGapEnum::class, 'Межконтактный зазор')->name,
        );
    }
}
