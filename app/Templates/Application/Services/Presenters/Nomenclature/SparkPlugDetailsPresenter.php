<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Templates\Domain\Enums\SparkPlug\ElectrodeSideCountEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Templates\Domain\ModelData\Nomenclature\SparkPlugDetailsData;

/** Рендерит форму `sparkPlugs` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class SparkPlugDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return [
            'Размер резьбы', 'Шаг резьбы', 'Длина резьбы',
            'Зазор электрода', 'Количество боковых электродов',
            'Ширина под ключ',
            'Момент затяжки мин, Н·м', 'Момент затяжки макс, Н·м',
            ...$this->metricsHeadings(),
        ];
    }

    public function cells(SparkPlugDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(ThreadSizeEnum::class, $data->thread->size),
            $this->nameToLabelCell(ThreadPitchEnum::class, $data->thread->pitch),
            $this->nameToLabelCell(ThreadLengthEnum::class, $data->thread->length),
            $this->nameToLabelCell(ElectrodeGapEnum::class, $data->electrode->gap),
            $this->nameToLabelCell(ElectrodeSideCountEnum::class, $data->electrode->countSide),
            $this->nameToLabelCell(WrenchJawWidthEnum::class, $data->wrenchJawWidth),
            $data->tighteningTorque->min,
            $data->tighteningTorque->max,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
