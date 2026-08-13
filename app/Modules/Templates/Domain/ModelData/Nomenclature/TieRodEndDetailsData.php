<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `tieRodEnd` (Nomenclature). Портируется декларативно 1-в-1 из
 * `TieRodEndTemplate` dan-center, без тестового покрытия — подключение к реальному Import/Export
 * ещё не сделано. `length` здесь число (в отличие от `TieRodDetailsData::$length` — строка).
 */
#[MapName(SnakeCaseMapper::class)]
final class TieRodEndDetailsData extends AbstractDetailsData
{
    /**
     * Фиксирует параметры наконечника рулевой тяги в nomenclature details template.
     */
    public function __construct(
        #[MapName('thread_1')]
        public readonly string $thread1,
        #[MapName('thread_2')]
        public readonly string $thread2,
        public readonly float $length,
        public readonly float $coneSize,
        public readonly string $taper,
        public readonly NomenclatureMetricsData $metrics,
    ) {}
}
