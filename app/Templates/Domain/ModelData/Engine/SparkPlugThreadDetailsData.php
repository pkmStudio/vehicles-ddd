<?php

declare(strict_types=1);

namespace App\Templates\Domain\ModelData\Engine;

use Spatie\LaravelData\Data;

/**
 * Резьба свечи зажигания. Порядок = порядок колонок Excel: size, pitch, length. Чистый
 * объект-значение — сборка из строки (`DetailsDataFactory`) и рендер в Excel-ячейки
 * (`DetailsDataPresenter`) сюда не входят.
 */
final class SparkPlugThreadDetailsData extends Data
{
    public function __construct(
        public readonly ?string $size = null,
        public readonly ?string $pitch = null,
        public readonly ?string $length = null,
    ) {}
}
