<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `stabilizerLink` (Nomenclature). Портируется декларативно 1-в-1 из
 * `StabilizerLinkTemplate` dan-center, без тестового покрытия — подключение к реальному
 * Import/Export ещё не сделано. Самая "урезанная" версия семейства резьбовых тяг/шарниров —
 * без `cone_size`/`taper`.
 */
#[MapName(SnakeCaseMapper::class)]
final class StabilizerLinkDetailsData extends AbstractDetailsData
{
    public function __construct(
        #[MapName('thread_1')]
        public readonly ?string $thread1 = null,
        #[MapName('thread_2')]
        public readonly ?string $thread2 = null,
        public readonly ?float $length = null,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
