<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `wheelHub` (Nomenclature). Портируется декларативно 1-в-1 из `WheelHubTemplate`
 * dan-center, без тестового покрытия — подключение к реальному Import/Export ещё не сделано.
 * В отличие от `WheelHubBearingDetailsData` — `height`/`outerDiameter` здесь числа, не строки
 * (намеренное расхождение между двумя похожими шаблонами, не опечатка).
 */
#[MapName(SnakeCaseMapper::class)]
final class WheelHubDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly float $height,
        public readonly string $abs,
        #[MapName('mount_1')]
        public readonly string $mount1,
        #[MapName('mount_2')]
        public readonly string $mount2,
        #[MapName('mount_3')]
        public readonly string $mount3,
        public readonly string $innerDiameter,
        public readonly int $splinesCount,
        public readonly float $outerDiameter,
        public readonly NomenclatureMetricsData $metrics,
    ) {}
}
