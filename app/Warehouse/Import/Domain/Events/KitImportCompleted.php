<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Events;

/**
 * Факт завершения импорта Kit (успешного или с частичными ошибками — детали в cache).
 */
final readonly class KitImportCompleted extends AbstractImportCompleted {}
