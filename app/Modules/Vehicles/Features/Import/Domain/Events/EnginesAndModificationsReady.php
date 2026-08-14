<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Events;

/**
 * Факт: двигатели и модификации импортированы — можно импортировать связи engine_modification.
 * Гейт готовности (кэш-флаги + публикация события) — infrastructure adapter import-фичи.
 */
final readonly class EnginesAndModificationsReady {}
