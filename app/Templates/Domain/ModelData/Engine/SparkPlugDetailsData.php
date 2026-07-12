<?php

declare(strict_types=1);

namespace App\Templates\Domain\ModelData\Engine;

use App\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `sparkPlugs`. Порядок свойств = порядок колонок Excel (было:
 * `SparkPlugTemplate::initializeFields()` — thread{size,pitch,length}, electrode{gap},
 * wrench_jaw_width). Чистый объект-значение — сборка из строки (`DetailsDataFactory`) и рендер
 * в Excel-ячейки (`DetailsDataPresenter`) сюда не входят.
 */
#[MapName(SnakeCaseMapper::class)]
final class SparkPlugDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly SparkPlugThreadDetailsData $thread = new SparkPlugThreadDetailsData,
        public readonly SparkPlugElectrodeDetailsData $electrode = new SparkPlugElectrodeDetailsData,
        public readonly ?string $wrenchJawWidth = null,
    ) {}
}
