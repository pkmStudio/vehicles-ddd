<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Engine;

use Spatie\LaravelData\Data;

/**
 * Размеры масляного фильтра (было: `CommonFields::metrics()` — вложенный объект). Чистый
 * объект-значение — сборка из строки (`DetailsDataFactory`) и рендер в Excel-ячейки
 * (`DetailsDataPresenter`) сюда не входят.
 */
final class OilFilterMetricsData extends Data
{
    public function __construct(
        /** @var array<int, float> */
        public readonly array $length = [],
        /** @var array<int, float> */
        public readonly array $width = [],
        /** @var array<int, float> */
        public readonly array $height = [],
    ) {}
}
