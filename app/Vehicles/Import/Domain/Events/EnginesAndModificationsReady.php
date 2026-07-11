<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Events;

/**
 * Факт: двигатели и модификации импортированы — можно импортировать связи engine_modification.
 * Гейт готовности (кэш-флаги + диспатч) — в EngineModificationReadinessGate (Application/Import/Services).
 */
final readonly class EnginesAndModificationsReady
{
}
