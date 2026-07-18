<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `wheelHubBearing` (Nomenclature). Портируется декларативно 1-в-1 из
 * `WheelHubBearingTemplate` dan-center, без тестового покрытия — подключение к реальному
 * Import/Export ещё не сделано.
 *
 * `height` — строка, не число: в реальных данных встречаются составные значения вида
 * "37 / 40,3" (комментарий в исходнике). В отличие от `WheelHubDetailsData::$height` (число) —
 * это не опечатка, а намеренное расхождение между двумя похожими шаблонами.
 *
 * `mount1`/`mount2` — явный `#[MapName]`, т.к. `Str::snake()` не вставляет `_` перед цифрой без
 * буквенной границы (`mount1` → `mount1`, а не `mount_1`), классовый `SnakeCaseMapper` тут не
 * справится сам.
 */
#[MapName(SnakeCaseMapper::class)]
final class WheelHubBearingDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly ?string $height = null,
        public readonly ?string $abs = null,
        #[MapName('mount_1')]
        public readonly ?string $mount1 = null,
        #[MapName('mount_2')]
        public readonly ?string $mount2 = null,
        public readonly ?string $innerDiameter = null,
        public readonly ?string $outerDiameter = null,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
