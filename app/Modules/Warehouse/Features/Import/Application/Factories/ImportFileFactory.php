<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Factories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\FileImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;

/**
 * Выбирает Excel-адаптер Warehouse-импорта по типу запроса.
 */
final readonly class ImportFileFactory implements ImportFileFactoryInterface
{
    /**
     * Возвращает импортный адаптер для конкретного типа Warehouse-каталога.
     */
    public function make(ImportTypeEnum $type): FileImportInterface
    {
        return match ($type) {
            ImportTypeEnum::Nomenclature => app(
                abstract: NomenclatureImportInterface::class,
            ),
            ImportTypeEnum::PackDimension => app(
                abstract: PackDimensionImportInterface::class,
            ),
            ImportTypeEnum::Kit => app(
                abstract: KitImportInterface::class,
            ),
        };
    }
}
