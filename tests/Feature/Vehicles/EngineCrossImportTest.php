<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\AssignEngineGroupServiceInterface;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Infrastructure\Imports\Engine\EngineCrossImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для EngineCrossImport. Гоняет collection() напрямую
 * (без реального Excel::import) — WithMultipleSheets-самоссылка тут не в фокусе теста,
 * баг жил в processRow()/$this->service.
 */
final class EngineCrossImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_group_to_engine_by_code(): void
    {
        $engine = Engine::query()->create(['eng_id' => 500, 'code_engine' => 'M54B30']);

        $import = new EngineCrossImport(app(AssignEngineGroupServiceInterface::class));

        $rows = new Collection([
            new Collection([7, 'M54B30']),
        ]);

        $import->collection($rows);

        $this->assertSame(7, $engine->fresh()->group_id);
    }
}
