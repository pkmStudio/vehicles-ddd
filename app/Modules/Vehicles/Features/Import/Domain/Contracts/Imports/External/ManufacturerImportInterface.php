<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External;

/**
 * Импортирует производителей (mfa_id, name, provider) одним листом.
 */
interface ManufacturerImportInterface extends FileImportInterface {}
