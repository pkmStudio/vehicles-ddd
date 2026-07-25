<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `wiper` (Nomenclature) — характеристики самого товара-щётки, НЕ то же самое, что
 * `Vehicle\WiperDetailsData` (потребность конкретного ТС/стороны). Портируется декларативно 1-в-1
 * из `WiperTemplate` dan-center, без тестового покрытия — подключение к реальному Import/Export
 * ещё не сделано. Единственный из семейств Nomenclature без блока `metrics` (габариты переданы
 * через `length_main/second/rear`). Чистый объект-значение — сборка/рендер сюда не входят.
 */
#[MapName(SnakeCaseMapper::class)]
final class WiperDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly string $position,
        public readonly string $category,
        public readonly string $construction,
        public readonly string $season,
        public readonly int $lengthMain,
        public readonly int $lengthSecond,
        public readonly int $lengthRear,
        /** @var array<int, string> */
        public readonly array $adapterTypeFront,
        /** @var array<int, string> */
        public readonly array $adapterTypeRear,
        public readonly string $coating,
        public readonly bool $wearSensor,
        public readonly bool $spoiler,
        public readonly bool $washerNozzle,
        public readonly bool $heated,
        public readonly string $steering,
    ) {}
}
