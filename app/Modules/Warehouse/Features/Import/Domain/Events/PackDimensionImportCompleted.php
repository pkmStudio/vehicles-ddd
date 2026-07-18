<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Events;

/**
 * Факт завершения импорта PackDimension (успешного или с частичными ошибками — детали в cache).
 */
final readonly class PackDimensionImportCompleted extends AbstractImportCompleted {}
