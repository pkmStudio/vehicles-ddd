<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services;

use App\Modules\Vehicles\Features\Import\Domain\Events\EnginesAndModificationsReady;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Координатор готовности процесса импорта: импорт двигателей и модификаций идут параллельно,
 * связь между ними можно строить лишь когда завершились ОБА. Гейт хранит флаги завершения
 * (в кэше — переживают разные queued-job'ы) и при готовности обоих диспатчит
 * EnginesAndModificationsReady. Сам объект без состояния (состояние — в кэше).
 */
final readonly class EngineModificationReadinessGate implements EngineModificationReadinessGateInterface
{
    private const FLAG_ENGINES = 'engines_imported';

    private const FLAG_MODIFICATIONS = 'modifications_imported';

    public function __construct(
        private Cache $cache,
        private Dispatcher $events,
    ) {}

    public function markEnginesImported(): void
    {
        $this->cache->forever(self::FLAG_ENGINES, true);
        $this->dispatchWhenReady();
    }

    public function markModificationsImported(): void
    {
        $this->cache->forever(self::FLAG_MODIFICATIONS, true);
        $this->dispatchWhenReady();
    }

    /**
     * Сбросить флаги (перед новым запуском импорта — на случай прерванного предыдущего).
     */
    public function reset(): void
    {
        $this->cache->forget(self::FLAG_ENGINES);
        $this->cache->forget(self::FLAG_MODIFICATIONS);
    }

    private function dispatchWhenReady(): void
    {
        if ($this->cache->get(self::FLAG_ENGINES) && $this->cache->get(self::FLAG_MODIFICATIONS)) {
            $this->reset();
            $this->events->dispatch(new EnginesAndModificationsReady);
        }
    }
}
