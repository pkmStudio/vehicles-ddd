<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для EngineMainSheetImport (edit-лист двигателей).
 */
final class EngineMainSheetImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что edit-лист двигателей обновляет только редактируемые поля существующего
     * двигателя (частичный upsert по eng_id, не создание новой записи).
     *
     * Шаги:
     * 1. Создаёт двигатель с исходными значениями (cylinder_count=6).
     * 2. Прогоняет одну строку через collection() с новыми значениями по eng_id=500.
     * 3. Проверяет, что БД отражает обновлённые поля (capacity/cylinder_count/valves).
     */
    public function test_updates_editable_engine_fields(): void
    {
        $engine = Engine::query()->create(['eng_id' => 500, 'code_engine' => 'M54B30', 'cylinder_count' => 6]);

        /** @var EngineMainSheetImport $import */
        $import = app()->makeWith(EngineMainSheetImport::class, [
            'cacheKey' => 'engine_main_sheet_test',
            'lockKey' => 'engine_main_sheet_test_lock',
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
            'number_of_valves' => 32,
        ]);
    }
}
