<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для EngineMainSheetImport (edit-лист двигателей).
 */
final class EngineMainSheetImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_editable_engine_fields(): void
    {
        $engine = Engine::query()->create(['eng_id' => 500, 'code_engine' => 'M54B30', 'cylinder_count' => 6]);

        /** @var EngineMainSheetImport $import */
        $import = app()->makeWith(EngineMainSheetImport::class, [
            'runId' => 'test-run-main-sheet',
            'cacheKey' => 'engine_main_sheet_test',
        ]);

        // [eng_id, _, engine_capacity, _, ps_start, ps_upto, cylinder_count, cylinder_diameter, valves]
        $rows = new Collection([
            new Collection([500, null, '2979', null, 231, 231, 8, 3.2, 32]),
        ]);

        $import->collection($rows);

        $this->assertDatabaseHas('engines', [
            'eng_id' => 500,
            'engine_capacity' => '2979',
            'cylinder_count' => 8,
            'eng_number_of_valves' => 32,
        ]);
    }
}
