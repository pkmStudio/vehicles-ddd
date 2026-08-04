<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Factories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\FileImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;

/**
 * Выбирает Excel-адаптер Warehouse-импорта на Infrastructure boundary.
 */
final readonly class ImportFileFactory implements ImportFileFactoryInterface
{
    public function make(ImportTypeEnum $type): FileImportInterface
    {
        return match ($type) {
            ImportTypeEnum::Nomenclature => app(NomenclatureImportInterface::class),
            ImportTypeEnum::PackDimension => app(PackDimensionImportInterface::class),
            ImportTypeEnum::Kit => app(KitImportInterface::class),
        };
    }
}
