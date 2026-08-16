<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Events\Modification;

use App\Modules\Vehicles\Features\Import\Domain\Events\AbstractImportCompleted;

/**
 * Факт завершения внешнего manager-импорта модификаций.
 */
final readonly class ModificationImportCompleted extends AbstractImportCompleted {}
