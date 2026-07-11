<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Exports;

/**
 * Экспортирует оба листа: Main + Spark Plugs.
 */
interface EngineMultiSheetExportInterface extends FileExportInterface
{
}
