<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Import\Domain\Contracts\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Vehicles\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class VehicleWipersSheetImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_wiper_sheet_does_not_update_vehicle_main_fields(): void
    {
        $manufacturer = Manufacturer::query()->create([
            'mfa_id' => 10,
            'name' => 'Skoda',
            'provider' => 'TD',
        ]);
        $vehicle = Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => 10,
            'ms_id' => 300,
            'name' => 'Octavia',
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
            'provider' => 'OD',
            'steering_type' => 'Левый руль',
        ]);

        $details = ['front' => ['count_wipers' => 2]];

        $templateDataBuilder = $this->mock(TemplateDataBuilderInterface::class);
        $templateDataBuilder->shouldReceive('buildBySlug')
            ->once()
            ->with(
                Mockery::on(fn (array $row): bool => $row[2] === 300 && $row[4] === 'Changed from wipers sheet'),
                20,
                DetailTemplateEnum::WIPER->value,
            )
            ->andReturn($details);

        $wiperSpec = $this->mock(VehicleWiperSpecificationImportServiceInterface::class);
        $wiperSpec->shouldReceive('importForVehicle')
            ->once()
            ->with(
                $vehicle->id,
                DetailTemplateEnum::WIPER->value,
                $details,
                'Левый руль',
                'Bosch Aerotwin',
                'Описание дворников',
            );

        /** @var VehicleWipersSheetImport $import */
        $import = app()->makeWith(VehicleWipersSheetImport::class, [
            'cacheKey' => 'vehicle_wipers_sheet_test',
            'lockKey' => 'vehicle_wipers_sheet_test_lock',
        ]);

        $rows = new Collection([
            new Collection([
                'A-1',
                10,
                300,
                'Skoda',
                'Changed from wipers sheet',
                'Octavia localized',
                'A7',
                'III',
                2013,
                2020,
                'Hatchback',
                'PC',
                'OD',
                null,
                'Левый руль',
                'Да',
                'Левый руль',
                DetailTemplateEnum::WIPER->value,
                'Bosch Aerotwin',
                'Описание дворников',
                2,
            ]),
        ]);

        $import->collection($rows);

        $this->assertSame(1, Vehicle::query()->count());
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'name' => 'Octavia',
        ]);
        $this->assertDatabaseMissing('vehicles', [
            'id' => $vehicle->id,
            'name' => 'Changed from wipers sheet',
        ]);
    }

    public function test_wiper_sheet_does_not_create_missing_vehicle(): void
    {
        $templateDataBuilder = $this->mock(TemplateDataBuilderInterface::class);
        $templateDataBuilder->shouldNotReceive('buildBySlug');

        $wiperSpec = $this->mock(VehicleWiperSpecificationImportServiceInterface::class);
        $wiperSpec->shouldNotReceive('importForVehicle');

        /** @var VehicleWipersSheetImport $import */
        $import = app()->makeWith(VehicleWipersSheetImport::class, [
            'cacheKey' => 'vehicle_wipers_missing_vehicle_test',
            'lockKey' => 'vehicle_wipers_missing_vehicle_test_lock',
        ]);

        $rows = new Collection([
            new Collection([
                'A-1',
                10,
                300,
                'Skoda',
                'Octavia',
                'Octavia localized',
                'A7',
                'III',
                2013,
                2020,
                'Hatchback',
                'PC',
                'OD',
                null,
                'Левый руль',
                'Да',
                'Левый руль',
                DetailTemplateEnum::WIPER->value,
                'Bosch Aerotwin',
                'Описание дворников',
                2,
            ]),
        ]);

        $import->collection($rows);

        $this->assertSame(0, Vehicle::query()->count());
    }
}
