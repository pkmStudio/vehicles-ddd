<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Enums;

/**
 * Классифицирует результат поиска номенклатуры публичного каталога.
 */
enum CatalogNomenclatureSearchMatchEnum: string
{
    case Empty = 'empty';
    case Exact = 'exact';
    case Multiple = 'multiple';
}
