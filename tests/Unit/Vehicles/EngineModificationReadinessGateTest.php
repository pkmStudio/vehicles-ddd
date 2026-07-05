<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Import\Application\Services\EngineModificationReadinessGate;
use App\Vehicles\Import\Domain\Events\EnginesAndModificationsReady;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

final class EngineModificationReadinessGateTest extends TestCase
{
    public function test_does_not_dispatch_until_both_imported(): void
    {
        $events = Mockery::mock(Dispatcher::class);
        $events->shouldNotReceive('dispatch');

        $gate = new EngineModificationReadinessGate(Cache::store(), $events);
        $gate->reset();

        $gate->markEnginesImported();

        $this->assertTrue(true); // диспатча не было — гейт ещё не готов
    }

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
