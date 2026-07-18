<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Vehicle;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Параметры передних щёток. Порядок свойств = порядок колонок Excel (было:
 * `WiperTemplate::createFrontField()` — length_main, length_second, adapter_type_front,
 * count_wipers). `adapterTypeFront` — массив хранимых имён `FrontAdapterTypeEnum` (формально
 * multi-select, `max:1` — по факту 0 или 1 элемент, но код это не enforces). Чистый
 * объект-значение — сборка из строки (`DetailsDataFactory`) и рендер в Excel-ячейки
 * (`DetailsDataPresenter`) сюда не входят.
 */
#[MapName(SnakeCaseMapper::class)]
final class WiperFrontDetailsData extends Data
{
    public function __construct(
        public readonly WiperLengthRangeData $lengthMain = new WiperLengthRangeData,
        public readonly WiperLengthRangeData $lengthSecond = new WiperLengthRangeData,
        /** @var array<int, string> */
        public readonly array $adapterTypeFront = [],
        public readonly ?int $countWipers = null,
    ) {}
}
