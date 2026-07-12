<?php

declare(strict_types=1);

namespace App\Templates\Domain\ModelData\Nomenclature;

use App\Templates\Domain\ModelData\AbstractDetailsData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Форма шаблона `sparkPlugs` (Nomenclature). Портируется декларативно 1-в-1 из
 * `SparkPlugTemplate` dan-center, без тестового покрытия — подключение к реальному Import/Export
 * ещё не сделано. Чистый объект-значение — сборка/рендер сюда не входят.
 */
#[MapName(SnakeCaseMapper::class)]
final class SparkPlugDetailsData extends AbstractDetailsData
{
    public function __construct(
        public readonly SparkPlugThreadDetailsData $thread = new SparkPlugThreadDetailsData,
        public readonly SparkPlugElectrodeDetailsData $electrode = new SparkPlugElectrodeDetailsData,
        public readonly ?string $wrenchJawWidth = null,
        public readonly TighteningTorqueData $tighteningTorque = new TighteningTorqueData,
        public readonly NomenclatureMetricsData $metrics = new NomenclatureMetricsData,
    ) {}
}
