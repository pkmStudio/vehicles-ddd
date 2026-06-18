<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class EnginesAndModificationsReady
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const FLAG_ENGINES = 'engines_imported';

    public const FLAG_MODIFICATIONS = 'modifications_imported';

    public function subscribe($events): void
    {
        $events->listen(
            EngineCommandImported::class,
            [self::class, 'handleEnginesImported']
        );

        $events->listen(
            ModificationCommandImported::class,
            [self::class, 'handleModificationsImported']
        );
    }

    public function handleEnginesImported(EngineCommandImported $event): void
    {
        Cache::forever(self::FLAG_ENGINES, true);
        $this->checkAndDispatch();
    }

    public function handleModificationsImported(ModificationCommandImported $event): void
    {
        Cache::forever(self::FLAG_MODIFICATIONS, true);
        $this->checkAndDispatch();
    }

    private function checkAndDispatch(): void
    {
        if (Cache::get(self::FLAG_ENGINES) && Cache::get(self::FLAG_MODIFICATIONS)) {
            self::clearFlags();
            event(new EnginesAndModificationsReady);
        }
    }

    public static function clearFlags(): void
    {
        Cache::forget(self::FLAG_ENGINES);
        Cache::forget(self::FLAG_MODIFICATIONS);
    }
}
