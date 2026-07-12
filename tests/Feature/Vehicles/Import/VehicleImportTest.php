<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Vehicles\Import\Domain\Contracts\Imports\Command\VehicleCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Vehicle\VehicleCommandImported;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для VehicleCommandImport. VehicleCommandImported
 * фейкается точечно, чтобы не запустить реальный command-каскад (StartModificationCommandImportListener).
 */
final class VehicleImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет реальный Excel-адаптер (Command/Repository/БД, не моки) на фикстурном CSV.
     *
     * Шаги:
     * 1. Фейкает VehicleCommandImported, чтобы не запустить реальный command-каскад
     *    (StartModificationCommandImportListener).
     * 2. Создаёт производителя, на которого сошлётся строка ТС.
     * 3. Гоняет import() на tests/Fixtures/vehicles_sample.csv через реальный Command.
     * 4. Проверяет, что в БД появилось ровно одно ТС с ожидаемыми полями.
     * 5. Проверяет, что событие завершения импорта продиспатчено.
     */
    public function test_imports_vehicles_from_csv_into_database(): void
    {
        Event::fake([VehicleCommandImported::class]);

        Manufacturer::query()->create(['mfa_id' => 10, 'name' => 'Skoda', 'provider' => 'TD']);

        $path = base_path('tests/Fixtures/vehicles_sample.csv');

        app(VehicleCommandImportInterface::class)->import($path);

        $this->assertSame(1, Vehicle::query()->count());

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 300,
            'mfa_id' => 10,
            'name' => 'Octavia',
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
        ]);

        Event::assertDispatched(VehicleCommandImported::class);
    }
}
