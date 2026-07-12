<?php

declare(strict_types=1);

namespace App\Templates\Domain\ModelData\Nomenclature;

use App\Templates\Domain\ModelData\AbstractDetailsData;
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
        public readonly ?float $height = null,
        public readonly ?string $abs = null,
        #[MapName('mount_1')]
        public readonly ?string $mount1 = null,
        #[MapName('mount_2')]
        public readonly ?string $mount2 = null,
        #[MapName('mount_3')]
        public readonly ?string $mount3 = null,
        public readonly ?string $innerDiameter = null,
        public readonly ?int $splinesCount = null,
        public readonly ?float $outerDiameter = null,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
