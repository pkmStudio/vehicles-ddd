<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Vehicles\Import\Infrastructure\Models\Engine;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use App\Vehicles\Import\Infrastructure\Models\Modification;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для EngineModificationImport (пивот engine_modification).
 */
final class EngineModificationImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет реальный Excel-адаптер (Command/Repository/БД, не моки) на фикстурном CSV.
     *
     * Шаги:
     * 1. Создаёт двигатель, производителя, ТС и модификацию, на которые сошлётся строка
     *    связи (по eng_id/mod_id).
     * 2. Гоняет import() на tests/Fixtures/engine_modification_sample.csv через реальный Command.
     * 3. Проверяет, что в pivot-таблице engine_modification появилась ожидаемая связь.
     */
    public function test_links_engine_to_modification_from_csv(): void
    {
        $engine = Engine::query()->create(['eng_id' => 500, 'code_engine' => 'M54B30']);

        $manufacturer = Manufacturer::query()->create(['mfa_id' => 10, 'name' => 'Skoda', 'provider' => 'TD']);
        $vehicle = Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => 10,
            'ms_id' => 300,
            'name' => 'Octavia',
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
        ]);
        $modification = Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => 300,
            'mod_id' => 50,
            'type' => 'PC',
        ]);

        $path = base_path('tests/Fixtures/engine_modification_sample.csv');

        app(EngineModificationImportInterface::class)->import($path);

        $this->assertDatabaseHas('engine_modification', [
            'engine_id' => $engine->id,
            'modification_id' => $modification->id,
            'eng_id' => 500,
            'mod_id' => 50,
            'type' => 'PC',
        ]);
    }
}
