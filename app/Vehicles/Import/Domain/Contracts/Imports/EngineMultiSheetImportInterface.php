<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Imports;

/**
 * Импортирует оба листа: Main + SparkPlugs.
 */
interface EngineMultiSheetImportInterface extends FileImportInterface
{
}
