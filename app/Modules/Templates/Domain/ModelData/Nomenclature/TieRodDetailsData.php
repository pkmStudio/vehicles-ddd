<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `tieRod` (Nomenclature). Портируется декларативно 1-в-1 из `TieRodTemplate`
 * dan-center, без тестового покрытия — подключение к реальному Import/Export ещё не сделано.
 * `length` здесь строка (в отличие от `TieRodEndDetailsData::$length` — число). `application` —
 * в dan-center хранился литеральным массивом (`axial_joint`/`rod`), здесь типизирован как
 * `TieRod\ApplicationEnum`.
 */
#[MapName(SnakeCaseMapper::class)]
final class TieRodDetailsData extends AbstractDetailsData
{
    public function __construct(
        #[MapName('thread_1')]
        public readonly ?string $thread1 = null,
        #[MapName('thread_2')]
        public readonly ?string $thread2 = null,
        public readonly ?string $length = null,
        public readonly ?float $coneSize = null,
        public readonly ?string $taper = null,
        public readonly ?string $application = null,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
