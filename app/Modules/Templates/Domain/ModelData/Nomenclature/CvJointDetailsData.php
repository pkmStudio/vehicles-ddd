<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `cvJoint` (Nomenclature, ШРУС). Портируется декларативно 1-в-1 из
 * `CvJointTemplate` dan-center, без тестового покрытия — подключение к реальному Import/Export
 * ещё не сделано. Единственная резьба — `thread_1`, без пары (в отличие от остальных шаблонов
 * этого семейства).
 */
#[MapName(SnakeCaseMapper::class)]
final class CvJointDetailsData extends AbstractDetailsData
{
    public function __construct(
        #[MapName('thread_1')]
        public readonly ?string $thread1 = null,
        #[MapName('length_1')]
        public readonly ?float $length1 = null,
        #[MapName('length_2')]
        public readonly ?float $length2 = null,
        public readonly ?string $abs = null,
        public readonly ?float $sealDiameter = null,
        public readonly ?int $splinesOuter = null,
        public readonly ?int $splinesInner = null,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
