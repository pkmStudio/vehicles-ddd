<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\ModelData\Vehicle;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Параметры задних щёток. Порядок свойств = порядок колонок Excel (было:
 * `WiperTemplate::createBackField()` — length_rear, adapter_type_rear, count_wipers). Чистый
 * объект-значение — сборка из строки (`DetailsDataFactory`) и рендер в Excel-ячейки
 * (`DetailsDataPresenter`) сюда не входят.
 */
#[MapName(SnakeCaseMapper::class)]
final class WiperBackDetailsData extends Data
{
    public function __construct(
        public readonly WiperLengthRangeData $lengthRear = new WiperLengthRangeData,
        /** @var array<int, string> */
        public readonly array $adapterTypeRear = [],
        public readonly ?int $countWipers = null,
    ) {}
}
