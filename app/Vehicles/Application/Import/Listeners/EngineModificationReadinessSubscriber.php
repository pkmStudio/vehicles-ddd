<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Listeners;

use App\Vehicles\Application\Import\Services\EngineModificationReadinessGate;
use App\Vehicles\Domain\Events\Engine\EngineCommandImported;
use App\Vehicles\Domain\Events\Modification\ModificationCommandImported;
use Illuminate\Events\Dispatcher;

/**
 * Тонкий подписчик: слушает завершения импортов двигателей и модификаций,
 * делегирует координацию готовности в EngineModificationReadinessGate.
 */
final readonly class EngineModificationReadinessSubscriber
{
    public function __construct(
        private EngineModificationReadinessGate $gate,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(EngineCommandImported::class, [self::class, 'onEnginesImported']);
        $events->listen(ModificationCommandImported::class, [self::class, 'onModificationsImported']);
    }

    public function onEnginesImported(EngineCommandImported $event): void
    {
        $this->gate->markEnginesImported();
    }

    public function onModificationsImported(ModificationCommandImported $event): void
    {
        $this->gate->markModificationsImported();
    }
}
