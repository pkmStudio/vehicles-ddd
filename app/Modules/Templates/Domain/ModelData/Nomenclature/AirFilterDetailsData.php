<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `airFilter` (Nomenclature). Портируется декларативно 1-в-1 из `AirFilterTemplate`
 * dan-center, без тестового покрытия — подключение к реальному Import/Export ещё не сделано.
 * Структурно идентична `CabinFilterDetailsData` (в dan-center — тоже два разных, но одинаковых
 * по полям класса), различие только в `type_id`/шаблоне-ключе, к которому она привязана.
 */
#[MapName(SnakeCaseMapper::class)]
final class AirFilterDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly string $performance,
        public readonly string $form,
        public readonly bool $frame,
        public readonly string $filterType,
        public readonly NomenclatureMetricsData $metrics,
    ) {}
}
