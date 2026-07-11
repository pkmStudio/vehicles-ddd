<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

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
