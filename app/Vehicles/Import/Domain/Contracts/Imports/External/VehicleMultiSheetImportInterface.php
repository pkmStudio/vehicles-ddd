<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Imports\External;

/**
 * Импортирует оба листа: Main + Wipers.
 */
interface VehicleMultiSheetImportInterface extends FileImportInterface
{
}
