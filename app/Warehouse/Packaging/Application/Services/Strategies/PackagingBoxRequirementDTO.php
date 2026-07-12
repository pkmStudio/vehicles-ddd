<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services\Strategies;

/**
 * Требуемые габариты/вес упаковки для одного расчёта `AbstractPackagingStrategy::calculatePak()`.
 * Упрощённый (readonly, без сеттеров) аналог dan-center `PakDimensionDTO` — там был мутабельный
 * класс с гетерами/сеттерами, но ни один вызывающий код сеттеры не использовал.
 */
final readonly class PackagingBoxRequirementDTO
{
    public function __construct(
        public int $weight,
        public float $width,
        public float $height,
        public float $length,
    ) {}
}
