<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Регрессионный тест на строгий формат основного листа EngineMainSheetImport.
 */
final class EngineMainSheetImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что основной лист двигателей обновляет существующий двигатель из полной
     * строки с обязательными характеристиками.
     *
     * Шаги:
     * 1. Создаёт двигатель с исходными значениями (cylinder_count=6).
     * 2. Прогоняет одну полную строку через collection() с новыми значениями по eng_id=500.
     * 3. Проверяет, что БД отражает обновлённые обязательные и nullable поля.
     */
    public function test_updates_editable_engine_fields(): void
    {
        $engine = Engine::query()->create([
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'power_kw_start' => 170,
            'power_ps_start' => 231,
            'fuel_type' => 'бензин',
            'cylinder_count' => 6,
            'provider' => 'OD',
            'allow_change_fields' => [],
        ]);

        /** @var EngineMainSheetImport $import */
        $import = app()->makeWith(EngineMainSheetImport::class, [
            'cacheKey' => 'engine_main_sheet_test',
            'lockKey' => 'engine_main_sheet_test_lock',
        ]);

        // [eng_id, code_engine, engine_capacity, fuel_type, kw_start, kw_upto, ps_start, ps_upto, cylinders, diameter, valves]
        $rows = new Collection([
            new Collection([500, 'M54B30TU', '2979', 'бензин', 172, 180, 234, 245, 8, 3.2, 32]),
        ]);

        $import->collection($rows);

        $this->assertDatabaseHas('engines', [
            'eng_id' => 500,
            'code_engine' => 'M54B30TU',
            'power_kw_start' => 172,
            'power_kw_upto' => 180,
            'power_ps_start' => 234,
            'power_ps_upto' => 245,
            'engine_capacity' => 2979.0,
            'cylinder_count' => 8,
            'cylinder_diameter' => 3.2,
            'number_of_valves' => 32,
            'fuel_type' => 'бензин',
        ]);
    }
}
