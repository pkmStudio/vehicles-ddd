<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Engine;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\FormatsExportCells;
use App\Modules\Templates\Domain\Enums\SparkPlug\ElectrodeGapEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadLengthEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadPitchEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\ThreadSizeEnum;
use App\Modules\Templates\Domain\Enums\SparkPlug\WrenchJawWidthEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugElectrodeDetailsData;
use App\Modules\Templates\Domain\ModelData\Engine\SparkPlugThreadDetailsData;

/**
 * Рендерит форму `sparkPlugs` в плоский набор Excel-ячеек экспорта. Выделено из
 * `DetailsDataPresenter`. Простой класс без собственного порта — вызывается только оттуда.
 */
final readonly class SparkPlugDetailsPresenter extends AbstractDetailsPresenter
{
    use FormatsExportCells;

    /**
     * Этот метод возвращает колонки engine spark-plug шаблона.
     * Шаги:
     * 1) Добавляет заголовки блока резьбы.
     * 2) Добавляет заголовки блока электрода.
     * 3) Добавляет колонку ширины зева ключа.
     */
    public function headings(): array
    {
        return [
            ...$this->threadHeadings(),
            ...$this->electrodeHeadings(),
            'Ширина зева гаечного ключа (мм)',
        ];
    }

    /**
     * Этот метод возвращает заголовки блока резьбы свечи.
     * Шаги:
     * 1) Перечисляет размер, шаг и длину резьбы в порядке `threadCells()`.
     */
    private function threadHeadings(): array
    {
        return ['Размер резьбы', 'Шаг резьбы (мм)', 'Длина резьбы (мм)'];
    }

    /**
     * Этот метод возвращает заголовок блока электрода engine-свечи.
     * Шаги:
     * 1) Возвращает колонку межконтактного зазора.
     */
    private function electrodeHeadings(): array
    {
        return ['Межконтактный зазор (мм)'];
    }

    /**
     * Этот метод указывает Data-класс engine spark-plug presenter-а.
     * Шаги:
     * 1) Возвращает class-string `SparkPlugDetailsData` для восстановления details массива.
     *
     * @return class-string<SparkPlugDetailsData>
     */
    protected function dataClass(): string
    {
        return SparkPlugDetailsData::class;
    }

    /**
     * Этот метод рендерит engine spark-plug details в Excel-ячейки.
     * Шаги:
     * 1) Проверяет, что получен `SparkPlugDetailsData`.
     * 2) Разворачивает вложенные блоки резьбы и электрода.
     * 3) Переводит ширину зева ключа из enum-name в Excel-label.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, SparkPlugDetailsData::class);

        return [
            ...$this->threadCells($data->thread),
            ...$this->electrodeCells($data->electrode),
            $this->nameToLabelCell(WrenchJawWidthEnum::class, $data->wrenchJawWidth),
        ];
    }

    /**
     * Этот метод рендерит блок резьбы свечи.
     * Шаги:
     * 1) Переводит размер, шаг и длину резьбы из enum names в labels.
     * 2) Возвращает три ячейки в порядке заголовков.
     */
    private function threadCells(SparkPlugThreadDetailsData $thread): array
    {
        return [
            $this->nameToLabelCell(ThreadSizeEnum::class, $thread->size),
            $this->nameToLabelCell(ThreadPitchEnum::class, $thread->pitch),
            $this->nameToLabelCell(ThreadLengthEnum::class, $thread->length),
        ];
    }

    /**
     * Этот метод рендерит блок электрода engine-свечи.
     * Шаги:
     * 1) Переводит межконтактный зазор из enum-name в Excel-label.
     * 2) Возвращает одну ячейку блока.
     */
    private function electrodeCells(SparkPlugElectrodeDetailsData $electrode): array
    {
        return [$this->nameToLabelCell(ElectrodeGapEnum::class, $electrode->gap)];
    }
}
