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
        public readonly ?string $position = null,
        public readonly ?string $construction = null,
        public readonly ?string $season = null,
        public readonly ?int $lengthMain = null,
        public readonly ?int $lengthSecond = null,
        public readonly ?int $lengthRear = null,
        /** @var array<int, string> */
        public readonly array $adapterTypeFront = [],
        /** @var array<int, string> */
        public readonly array $adapterTypeRear = [],
        public readonly ?string $coating = null,
        public readonly ?bool $wearSensor = null,
        public readonly ?bool $spoiler = null,
        public readonly ?bool $washerNozzle = null,
        public readonly ?bool $heated = null,
        public readonly ?string $steering = null,
    ) {}
}
