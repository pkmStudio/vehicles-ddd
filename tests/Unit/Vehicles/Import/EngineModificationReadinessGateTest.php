<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Vehicles\Import\Application\Services\EngineModificationReadinessGate;
use App\Vehicles\Import\Domain\Events\EnginesAndModificationsReady;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

final class EngineModificationReadinessGateTest extends TestCase
{
    /**
     * Проверяет, что готовность одной половины (двигатели ИЛИ модификации) недостаточна для
     * диспатча — гейт ждёт обе.
     *
     * Шаги:
     * 1. Мокает Dispatcher — ожидает, что dispatch() НЕ вызовется вообще.
     * 2. Сбрасывает флаги гейта (reset), затем помечает только markEnginesImported().
     * 3. Mockery сам провалит тест, если dispatch() будет вызван.
     */
    public function test_does_not_dispatch_until_both_imported(): void
    {
        $events = Mockery::mock(Dispatcher::class);
        $events->shouldNotReceive('dispatch');

        $gate = new EngineModificationReadinessGate(Cache::store(), $events);
        $gate->reset();

        $gate->markEnginesImported();

        $this->assertTrue(true); // диспатча не было — гейт ещё не готов
    }

    /**
     * Проверяет основной сценарий: когда готовы обе половины — гейт диспатчит
     * EnginesAndModificationsReady ровно один раз и сбрасывает свои флаги в cache (готовность
     * не должна залипать навсегда после первого срабатывания).
     *
     * Шаги:
     * 1. Мокает Dispatcher — ожидает ровно один dispatch() с EnginesAndModificationsReady.
     * 2. Сбрасывает флаги, затем помечает markEnginesImported() и markModificationsImported().
     * 3. Проверяет, что событие продиспатчено ровно один раз.
     * 4. Проверяет, что оба cache-флага после этого снова пусты (сброшены).
     */
    public function test_dispatches_when_both_imported_and_resets_flags(): void
    {
        $dispatched = [];
        $events = Mockery::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(EnginesAndModificationsReady::class))
            ->andReturnUsing(function ($e) use (&$dispatched) {
                $dispatched[] = $e;
            });

        $cache = Cache::store();
        $gate = new EngineModificationReadinessGate($cache, $events);
        $gate->reset();

        $gate->markEnginesImported();
        $gate->markModificationsImported();

        $this->assertCount(1, $dispatched);
        // флаги сброшены после готовности
        $this->assertNull($cache->get('engines_imported'));
        $this->assertNull($cache->get('modifications_imported'));
    }

    /**
     * Проверяет, что результат не зависит от порядка готовности половин — двигатели и
     * модификации импортируются параллельно, порядок завершения не гарантирован.
     *
     * Шаги:
     * 1. Мокает Dispatcher — ожидает ровно один dispatch().
     * 2. Сбрасывает флаги, затем помечает markModificationsImported() ПЕРЕД
     *    markEnginesImported() (обратный порядок относительно предыдущего теста).
     * 3. Mockery подтверждает, что dispatch() всё равно случился один раз.
     */
    public function test_order_independent(): void
    {
        $events = Mockery::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(Mockery::type(EnginesAndModificationsReady::class));

        $gate = new EngineModificationReadinessGate(Cache::store(), $events);
        $gate->reset();

        // обратный порядок — результат тот же
        $gate->markModificationsImported();
        $gate->markEnginesImported();

        $this->addToAssertionCount(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
