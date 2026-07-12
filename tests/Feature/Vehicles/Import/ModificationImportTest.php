<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Vehicles\Import\Domain\Contracts\Imports\Command\ModificationCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Modification\ModificationCommandImported;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use App\Vehicles\Import\Infrastructure\Models\Modification;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для ModificationCommandImport.
 */
final class ModificationImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет реальный Excel-адаптер (Command/Repository/БД, не моки) на фикстурном CSV.
     *
     * Шаги:
     * 1. Фейкает ModificationCommandImported, чтобы не запустить реальный каскад слушателей.
     * 2. Создаёт производителя и ТС, на которые сошлётся строка модификации (по ms_id).
     * 3. Гоняет import() на tests/Fixtures/modifications_sample.csv через реальный Command.
     * 4. Проверяет, что в БД появилась ровно одна модификация с ожидаемыми полями.
     * 5. Проверяет, что событие завершения импорта продиспатчено.
     */
    public function test_imports_modifications_from_csv_into_database(): void
    {
        Event::fake([ModificationCommandImported::class]);

        $manufacturer = Manufacturer::query()->create(['mfa_id' => 10, 'name' => 'Skoda', 'provider' => 'TD']);
        Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => 10,
            'ms_id' => 300,
            'name' => 'Octavia',
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
        ]);

        $path = base_path('tests/Fixtures/modifications_sample.csv');

        app(ModificationCommandImportInterface::class)->import($path);

        $this->assertSame(1, Modification::query()->count());

        $this->assertDatabaseHas('modifications', [
            'ms_id' => 300,
            'mod_id' => 50,
            'type' => 'PC',
            'description' => '1.8 TSI',
            'power_ps' => 180,
        ]);

        Event::assertDispatched(ModificationCommandImported::class);
    }
}
