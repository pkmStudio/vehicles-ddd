<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
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
        $engine = Engine::query()->create([
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'power_kw_start' => 170,
            'power_ps_start' => 231,
            'fuel_type' => 'бензин',
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);

        $manufacturer = Manufacturer::query()->create(['mfa_id' => 10, 'name' => 'Skoda', 'provider' => 'TD']);
        $vehicle = Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => 10,
            'ms_id' => 300,
            'name' => 'Octavia',
            'generation' => 'III',
            'generation_year_from' => 2013,
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
            'provider' => ProviderEnum::TD->value,
            'steering_type' => 'Левый руль',
            'is_allow' => true,
        ]);
        $modification = Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => 300,
            'mod_id' => 50,
            'type' => 'PC',
            'year_from' => 2013,
            'description' => '1.4 TSI',
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => ['year_from', 'year_to'],
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
