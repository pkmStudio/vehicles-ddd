<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\Exports;

/**
 * Экспортирует номенклатуру одного типа: лист данных + лист справочников.
 */
interface NomenclatureByTypeExportInterface extends FileExportInterface {}
