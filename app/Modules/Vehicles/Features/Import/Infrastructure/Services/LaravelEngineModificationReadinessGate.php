<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Services;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\EnginesAndModificationsReady;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel cache adapter для rendezvous gate импорта двигателей и модификаций.
 */
final readonly class LaravelEngineModificationReadinessGate implements EngineModificationReadinessGateInterface
{
    /**
     * Отмечает завершение импорта двигателей.
     *
     * Шаги:
     * 1) Записать cache-флаг готовности engines.
     * 2) Проверить, готовы ли обе import-ветки.
     */
    public function markEnginesImported(): void
    {
        Cache::forever(self::FLAG_ENGINES, true);
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
        Cache::forever(self::FLAG_MODIFICATIONS, true);
        $this->dispatchWhenReady();
    }

    /**
     * Сбросить флаги перед новым запуском импорта или после готовности обеих веток.
     *
     * Шаги:
     * 1) Удалить cache-флаг готовности engines.
     * 2) Удалить cache-флаг готовности modifications.
     */
    public function reset(): void
    {
        Cache::forget(self::FLAG_ENGINES);
        Cache::forget(self::FLAG_MODIFICATIONS);
    }

    /**
     * Публикует событие готовности, когда обе import-ветки завершены.
     *
     * Шаги:
     * 1) Прочитать cache-флаги engines и modifications.
     * 2) Если хотя бы одна ветка не готова — ничего не делать.
     * 3) Сбросить оба флага перед публикацией события.
     * 4) Опубликовать `EnginesAndModificationsReady`.
     */
    private function dispatchWhenReady(): void
    {
        $enginesImported = (bool) Cache::get(self::FLAG_ENGINES);
        $modificationsImported = (bool) Cache::get(self::FLAG_MODIFICATIONS);

        if ($enginesImported && $modificationsImported) {
            $this->reset();
            event(new EnginesAndModificationsReady);
        }
    }
}
