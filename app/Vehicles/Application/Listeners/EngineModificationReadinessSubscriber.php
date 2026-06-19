<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\Engine\EngineCommandImported;
use App\Vehicles\Domain\Events\EnginesAndModificationsReady;
use App\Vehicles\Domain\Events\Modification\ModificationCommandImported;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;

/**
 * Гейт готовности: ждёт импорт двигателей И модификаций (кэш-флаги),
 * при готовности обоих — диспатчит EnginesAndModificationsReady.
 */
final class EngineModificationReadinessSubscriber
{
    public const FLAG_ENGINES = 'engines_imported';

    public const FLAG_MODIFICATIONS = 'modifications_imported';

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(EngineCommandImported::class, [self::class, 'onEnginesImported']);
        $events->listen(ModificationCommandImported::class, [self::class, 'onModificationsImported']);
    }

    public function onEnginesImported(EngineCommandImported $event): void
    {
        Cache::forever(self::FLAG_ENGINES, true);
        $this->dispatchIfReady();
    }

    public function onModificationsImported(ModificationCommandImported $event): void
    {
        Cache::forever(self::FLAG_MODIFICATIONS, true);
        $this->dispatchIfReady();
    }

    public static function clearFlags(): void
    {
        Cache::forget(self::FLAG_ENGINES);
        Cache::forget(self::FLAG_MODIFICATIONS);
    }

    private function dispatchIfReady(): void
    {
        if (Cache::get(self::FLAG_ENGINES) && Cache::get(self::FLAG_MODIFICATIONS)) {
            self::clearFlags();
            event(new EnginesAndModificationsReady);
        }
    }
}
