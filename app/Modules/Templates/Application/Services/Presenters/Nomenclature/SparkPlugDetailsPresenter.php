<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeSideCountEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\SparkPlugDetailsData;

/** Рендерит форму `sparkPlugs` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class SparkPlugDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return [
            'Размер резьбы', 'Шаг резьбы (мм)', 'Длина резьбы (мм)',
            'Межконтактный зазор (мм)', 'Число боковых электродов',
            'Ширина зева гаечного ключа (мм)',
            'От', 'До',
            ...$this->metricsHeadings(),
        ];
    }

    /** @return class-string<SparkPlugDetailsData> */
    protected function dataClass(): string
    {
        return SparkPlugDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, SparkPlugDetailsData::class);

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
