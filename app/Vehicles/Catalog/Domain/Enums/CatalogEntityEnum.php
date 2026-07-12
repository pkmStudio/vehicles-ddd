<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Enums;

/**
 * Перечисляет допустимые значения потока мутаций каталога.
 */
enum CatalogEntityEnum: string
{
    case Vehicle = 'vehicle';
    case Manufacturer = 'manufacturer';
    case Engine = 'engine';
    case Modification = 'modification';
}
