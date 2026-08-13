<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `ballJoint` (Nomenclature). Портируется декларативно 1-в-1 из `BallJointTemplate`
 * dan-center, без тестового покрытия — подключение к реальному Import/Export ещё не сделано.
 */
#[MapName(SnakeCaseMapper::class)]
final class BallJointDetailsData extends AbstractDetailsData
{
    /**
     * Фиксирует параметры шаровой опоры в nomenclature details template.
     */
    public function __construct(
        #[MapName('thread_1')]
        public readonly string $thread1,
        #[MapName('thread_2')]
        public readonly string $thread2,
        public readonly float $length,
        public readonly string $outerDiameter,
        public readonly float $coneSize,
        public readonly string $taper,
        public readonly NomenclatureMetricsData $metrics,
    ) {}
}
