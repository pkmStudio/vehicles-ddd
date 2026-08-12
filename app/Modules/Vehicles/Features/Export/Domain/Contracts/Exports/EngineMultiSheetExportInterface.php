<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports;

/**
 * Экспортирует оба листа: Main + Spark Plugs.
 */
interface EngineMultiSheetExportInterface extends FileExportInterface {}
