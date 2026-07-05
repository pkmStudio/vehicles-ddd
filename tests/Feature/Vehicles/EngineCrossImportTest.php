<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineCrossImportInterface;
use App\Vehicles\Domain\DTOs\ImportRunContext;
use App\Vehicles\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Vehicles\Domain\Models\Engine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 (баг $this->useCase) и §6.3 (ImportRunContext вместо
 * Auth::id(), userId обязателен и приходит явно, гейт ">0" убран — событие диспатчится всегда).
 */
final class EngineCrossImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_group_to_engine_by_code(): void
    {
        Event::fake([EngineCrossImportCompleted::class]);

        $engine = Engine::query()->create(['eng_id' => 500, 'code_engine' => 'M54B30']);

        $context = new ImportRunContext(userId: 42, runId: 'test-run-cross');
        $path = base_path('tests/Fixtures/engine_groups_sample.csv');

        app(EngineCrossImportInterface::class)->import($path, $context);

        $this->assertSame(7, $engine->fresh()->group_id);

        Event::assertDispatched(
            EngineCrossImportCompleted::class,
            fn (EngineCrossImportCompleted $event): bool => $event->userId === 42,
        );
    }
}
