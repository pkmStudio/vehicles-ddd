<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External;

/**
 * Импортирует оба листа: Main + Wipers.
 */
interface VehicleMultiSheetImportInterface extends FileImportInterface {}
