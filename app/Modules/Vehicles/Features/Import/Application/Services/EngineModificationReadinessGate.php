<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\EnginesAndModificationsReady;
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

    /**
     * Инициализирует cache и event dispatcher для rendezvous gate.
     *
     * Шаги:
     * 1) Сохранить cache repository для флагов завершения import-веток.
     * 2) Сохранить event dispatcher для публикации события готовности.
     */
    public function __construct(
        private Cache $cache,
        private Dispatcher $events,
    ) {}

    /**
     * Отмечает завершение импорта двигателей.
     *
     * Шаги:
     * 1) Записать cache-флаг готовности engines.
     * 2) Проверить, готовы ли обе import-ветки.
     */
    public function markEnginesImported(): void
    {
        $this->cache->forever(self::FLAG_ENGINES, true);
        $this->dispatchWhenReady();
    }

    /**
     * Отмечает завершение импорта модификаций.
     *
     * Шаги:
     * 1) Записать cache-флаг готовности modifications.
     * 2) Проверить, готовы ли обе import-ветки.
     */
    public function markModificationsImported(): void
    {
        $this->cache->forever(self::FLAG_MODIFICATIONS, true);
        $this->dispatchWhenReady();
    }

    /**
     * Сбросить флаги (перед новым запуском импорта — на случай прерванного предыдущего).
     *
     * Шаги:
     * 1) Удалить cache-флаг готовности engines.
     * 2) Удалить cache-флаг готовности modifications.
     */
    public function reset(): void
    {
        $this->cache->forget(self::FLAG_ENGINES);
        $this->cache->forget(self::FLAG_MODIFICATIONS);
    }

    /**
     * Публикует событие готовности, когда обе import-ветки завершены.
     *
     * Шаги:
     * 1) Прочитать cache-флаги engines и modifications.
     * 2) Если хотя бы одна ветка не готова — ничего не делать.
     * 3) Сбросить оба флага перед публикацией события.
     * 4) Dispatch `EnginesAndModificationsReady`.
     */
    private function dispatchWhenReady(): void
    {
        $enginesImported = (bool) $this->cache->get(self::FLAG_ENGINES);
        $modificationsImported = (bool) $this->cache->get(self::FLAG_MODIFICATIONS);

        if ($enginesImported && $modificationsImported) {
            $this->reset();
            $this->events->dispatch(new EnginesAndModificationsReady);
        }
    }
}
