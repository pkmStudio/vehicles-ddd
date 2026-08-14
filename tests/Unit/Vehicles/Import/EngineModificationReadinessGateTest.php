<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\EnginesAndModificationsReady;
use App\Modules\Vehicles\Features\Import\Infrastructure\Services\LaravelEngineModificationReadinessGate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class EngineModificationReadinessGateTest extends TestCase
{
    /**
     * Проверяет, что готовность одной половины (двигатели ИЛИ модификации) недостаточна для
     * диспатча — гейт ждёт обе.
     *
     * Шаги:
     * 1. Фейкает события Laravel и ожидает, что EnginesAndModificationsReady НЕ будет опубликован.
     * 2. Сбрасывает флаги гейта (reset), затем помечает только markEnginesImported().
     * 3. Проверяет отсутствие события готовности.
     */
    public function test_does_not_dispatch_until_both_imported(): void
    {
        Event::fake([EnginesAndModificationsReady::class]);

        $gate = new LaravelEngineModificationReadinessGate;
        $gate->reset();

        $gate->markEnginesImported();

        Event::assertNotDispatched(EnginesAndModificationsReady::class);
    }

    /**
     * Проверяет основной сценарий: когда готовы обе половины — гейт диспатчит
     * EnginesAndModificationsReady ровно один раз и сбрасывает свои флаги в cache (готовность
     * не должна залипать навсегда после первого срабатывания).
     *
     * Шаги:
     * 1. Фейкает события Laravel и ожидает EnginesAndModificationsReady.
     * 2. Сбрасывает флаги, затем помечает markEnginesImported() и markModificationsImported().
     * 3. Проверяет, что событие продиспатчено ровно один раз.
     * 4. Проверяет, что оба cache-флага после этого снова пусты (сброшены).
     */
    public function test_dispatches_when_both_imported_and_resets_flags(): void
    {
        Event::fake([EnginesAndModificationsReady::class]);

        $gate = new LaravelEngineModificationReadinessGate;
        $gate->reset();

        $gate->markEnginesImported();
        $gate->markModificationsImported();

        Event::assertDispatched(EnginesAndModificationsReady::class, 1);
        $this->assertNull(Cache::get(EngineModificationReadinessGateInterface::FLAG_ENGINES));
        $this->assertNull(Cache::get(EngineModificationReadinessGateInterface::FLAG_MODIFICATIONS));
    }

    /**
     * Проверяет, что результат не зависит от порядка готовности половин — двигатели и
     * модификации импортируются параллельно, порядок завершения не гарантирован.
     *
     * Шаги:
     * 1. Фейкает события Laravel и ожидает ровно одну публикацию события готовности.
     * 2. Сбрасывает флаги, затем помечает markModificationsImported() ПЕРЕД
     *    markEnginesImported() (обратный порядок относительно предыдущего теста).
     * 3. Проверяет, что событие всё равно опубликовано один раз.
     */
    public function test_order_independent(): void
    {
        Event::fake([EnginesAndModificationsReady::class]);

        $gate = new LaravelEngineModificationReadinessGate;
        $gate->reset();

        // обратный порядок — результат тот же
        $gate->markModificationsImported();
        $gate->markEnginesImported();

        Event::assertDispatched(EnginesAndModificationsReady::class, 1);
    }
}
