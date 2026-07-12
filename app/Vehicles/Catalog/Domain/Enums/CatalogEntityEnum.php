<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Enums;

enum CatalogEntityEnum: string
{
    case Vehicle = 'vehicle';
    case Manufacturer = 'manufacturer';
    case Engine = 'engine';
    case Modification = 'modification';
}
