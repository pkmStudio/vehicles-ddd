<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\VehicleCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleCommandImported;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
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

    public function test_command_import_overwrites_existing_vehicle_as_tecdoc_source_of_truth(): void
    {
        Event::fake([VehicleCommandImported::class]);

        $manufacturer = Manufacturer::query()->create([
            'mfa_id' => 10,
            'name' => 'Skoda',
            'provider' => ProviderEnum::TD->value,
        ]);
        Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => 10,
            'ms_id' => 300,
            'name' => 'Old OD name',
            'generation' => 'OD generation',
            'generation_year_from' => 2000,
            'generation_year_to' => 2001,
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => CarcaseTypeEnum::SALOON->value,
            'provider' => ProviderEnum::OD->value,
            'steering_type' => SteeringTypeEnum::LEFT->value,
            'is_allow' => false,
        ]);

        app(VehicleCommandImportInterface::class)->import(base_path('tests/Fixtures/vehicles_sample.csv'));

        $this->assertSame(1, Vehicle::query()->count());
        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 300,
            'name' => 'Octavia',
            'generation' => 'A7',
            'generation_year_from' => 2013,
            'generation_year_to' => 2020,
            'type_carcase' => CarcaseTypeEnum::HATCHBACK->value,
            'provider' => ProviderEnum::TD->value,
        ]);
    }
}
