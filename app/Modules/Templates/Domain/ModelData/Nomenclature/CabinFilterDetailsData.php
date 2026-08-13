<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `cabinFilter` (Nomenclature). Структурно идентична `AirFilterDetailsData` (то же
 * в dan-center — `CabinFilterTemplate` отличается от `AirFilterTemplate` только именем/ключом, не
 * полями), портируется декларативно 1-в-1, без тестового покрытия — подключение к реальному
 * Import/Export ещё не сделано.
 */
#[MapName(SnakeCaseMapper::class)]
final class CabinFilterDetailsData extends AbstractDetailsData
{
    /**
     * Фиксирует параметры салонного фильтра в nomenclature details template.
     */
    public function __construct(
        public readonly string $performance,
        public readonly string $form,
        public readonly bool $frame,
        public readonly string $filterType,
        public readonly NomenclatureMetricsData $metrics,
    ) {}
}
