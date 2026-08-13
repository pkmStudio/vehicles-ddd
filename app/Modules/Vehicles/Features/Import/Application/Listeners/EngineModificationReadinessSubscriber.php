<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineCommandImported;
use App\Modules\Vehicles\Features\Import\Domain\Events\Modification\ModificationCommandImported;
use Illuminate\Events\Dispatcher;

/**
 * Тонкий подписчик: слушает завершения импортов двигателей и модификаций,
 * делегирует координацию готовности в EngineModificationReadinessGate.
 */
final readonly class EngineModificationReadinessSubscriber
{
    /**
     * Инициализирует gate готовности engine/modification импортов.
     *
     * Шаги:
     * 1) Сохранить readiness gate, который держит cache-состояние rendezvous.
     */
    public function __construct(
        private EngineModificationReadinessGateInterface $gate,
    ) {}

    /**
     * Регистрирует обработчики событий engine/modification импортов.
     *
     * Шаги:
     * 1) Подписать `EngineCommandImported` на handler готовности engines.
     * 2) Подписать `ModificationCommandImported` на handler готовности modifications.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(EngineCommandImported::class, [self::class, 'onEnginesImported']);
        $events->listen(ModificationCommandImported::class, [self::class, 'onModificationsImported']);
    }

    /**
     * Отмечает завершение command import двигателей.
     *
     * Шаги:
     * 1) Передать факт готовности engines в readiness gate.
     */
    public function onEnginesImported(EngineCommandImported $event): void
    {
        $this->gate->markEnginesImported();
    }

    /**
     * Отмечает завершение command import модификаций.
     *
     * Шаги:
     * 1) Передать факт готовности modifications в readiness gate.
     */
    public function onModificationsImported(ModificationCommandImported $event): void
    {
        $this->gate->markModificationsImported();
    }
}
