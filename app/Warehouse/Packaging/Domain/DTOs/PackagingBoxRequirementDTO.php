<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Domain\DTOs;

/**
 * Требуемые габариты/вес упаковки для одного расчёта `AbstractPackagingStrategy::calculatePackDimension()`.
 * Упрощённый (readonly, без сеттеров) аналог dan-center `PakDimensionDTO` — там был мутабельный
 * класс с гетерами/сеттерами, но ни один вызывающий код сеттеры не использовал.
 */
final readonly class PackagingBoxRequirementDTO
{
    /**
     * Хранит вес и габариты упаковки, которые должна обеспечить стратегия.
     */
    public function __construct(
        public int $weight,
        public float $width,
        public float $height,
        public float $length,
    ) {}
}
