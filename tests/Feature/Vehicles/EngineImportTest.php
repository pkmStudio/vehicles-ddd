<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Import\Domain\Contracts\Imports\Command\EngineCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Engine\EngineCommandImported;
use App\Vehicles\Import\Infrastructure\Models\Engine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для EngineCommandImport.
 */
final class EngineImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_engines_from_csv_into_database(): void
    {
        Event::fake([EngineCommandImported::class]);

        $path = base_path('tests/Fixtures/engines_sample.csv');

        app(EngineCommandImportInterface::class)->import($path);

        $this->assertSame(1, Engine::query()->count());

        $this->assertDatabaseHas('engines', [
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'cylinder_count' => 6,
            'eng_fuel_type' => 'бензин',
        ]);

        Event::assertDispatched(EngineCommandImported::class);
    }
}
