<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters;

use App\Modules\Templates\Application\Traits\FormatsExportCells;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugElectrodeDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugThreadDetailsData;

/**
 * Рендерит форму `sparkPlugs` в плоский набор Excel-ячеек экспорта. Выделено из
 * `DetailsDataPresenter`. Простой класс без собственного порта — вызывается только оттуда.
 */
final readonly class SparkPlugDetailsPresenter
{
    use FormatsExportCells;

    public function headings(): array
    {
        return [
            ...$this->threadHeadings(),
            ...$this->electrodeHeadings(),
            'Ширина зева гаечного ключа (мм)',
        ];
    }

    private function threadHeadings(): array
    {
        return ['Размер резьбы', 'Шаг резьбы (мм)', 'Длина резьбы (мм)'];
    }

    private function electrodeHeadings(): array
    {
        return ['Межконтактный зазор (мм)'];
    }

    public function cells(SparkPlugDetailsData $data): array
    {
        return [
            ...$this->threadCells($data->thread),
            ...$this->electrodeCells($data->electrode),
            $this->nameToLabelCell(WrenchJawWidthEnum::class, $data->wrenchJawWidth),
        ];
    }

    private function threadCells(SparkPlugThreadDetailsData $thread): array
    {
        return [
            $this->nameToLabelCell(ThreadSizeEnum::class, $thread->size),
            $this->nameToLabelCell(ThreadPitchEnum::class, $thread->pitch),
            $this->nameToLabelCell(ThreadLengthEnum::class, $thread->length),
        ];
    }

    private function electrodeCells(SparkPlugElectrodeDetailsData $electrode): array
    {
        return [$this->nameToLabelCell(ElectrodeGapEnum::class, $electrode->gap)];
    }
}
