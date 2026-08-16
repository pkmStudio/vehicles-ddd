<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Events\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\Events\AbstractImportCompleted;

/**
 * Факт завершения внешнего manager-импорта связей модификаций и двигателей.
 */
final readonly class EngineModificationImportCompleted extends AbstractImportCompleted {}
