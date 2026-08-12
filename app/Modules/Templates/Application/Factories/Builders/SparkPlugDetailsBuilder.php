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
    /**
     * Этот метод собирает engine spark-plug details из последовательных ячеек импорта.
     * Шаги:
     * 1) Собирает вложенный блок резьбы.
     * 2) Собирает вложенный блок электрода.
     * 3) Читает ширину зева ключа через enum-справочник.
     * 4) Возвращает `SparkPlugDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): SparkPlugDetailsData
    {
        return new SparkPlugDetailsData(
            thread: $this->buildThread($cursor),
            electrode: $this->buildElectrode($cursor),
            wrenchJawWidth: $cursor->pullRequiredLabel(WrenchJawWidthEnum::class, 'Ширина зева гаечного ключа')->name,
        );
    }

    /**
     * Этот метод собирает блок резьбы свечи зажигания.
     * Шаги:
     * 1) Читает размер резьбы как обязательный label `ThreadSizeEnum`.
     * 2) Читает шаг резьбы как обязательный label `ThreadPitchEnum`.
     * 3) Читает длину резьбы как обязательный label `ThreadLengthEnum`.
     * 4) Возвращает `SparkPlugThreadDetailsData` с enum-name значениями.
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
     * Этот метод собирает блок электрода свечи зажигания.
     * Шаги:
     * 1) Читает межконтактный зазор как обязательный label `ElectrodeGapEnum`.
     * 2) Сохраняет enum-name зазора в `SparkPlugElectrodeDetailsData`.
     */
    private function buildElectrode(DetailsRowCursor $cursor): SparkPlugElectrodeDetailsData
    {
        return new SparkPlugElectrodeDetailsData(
            gap: $cursor->pullRequiredLabel(ElectrodeGapEnum::class, 'Межконтактный зазор')->name,
        );
    }
}
