<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Import\Domain\Contracts\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Vehicles\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleWiperSheetRowMapper;
use App\Vehicles\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use RuntimeException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class VehicleWipersSheetImportTest extends TestCase
{
    use RefreshDatabase;

    private function rowMapper(array $row, array $details): VehicleWiperSheetRowMapper
    {
        $templateDataBuilder = $this->mock(TemplateDataBuilderInterface::class);
        $templateDataBuilder->shouldReceive('buildBySlug')
            ->once()
            ->with(Mockery::on(fn (array $mappedRow): bool => $mappedRow === $row), 20, DetailTemplateEnum::WIPER->value)
            ->andReturn($details);

        return new VehicleWiperSheetRowMapper(new ImportRowValueFormatter, $templateDataBuilder);
    }

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

        $row = [
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
        ];
        $rowMapper = $this->rowMapper($row, $details);

        $wiperSpec = $this->mock(VehicleWiperSpecificationImportServiceInterface::class);
        $wiperSpec->shouldReceive('upsertFromRow')
            ->once()
            ->with(Mockery::on(fn (VehicleWiperSheetRowDTO $dto): bool => $dto->msId === 300
                && $dto->templateSlug === DetailTemplateEnum::WIPER->value
                && $dto->featureValueName === 'Левый руль'
                && $dto->name === 'Bosch Aerotwin'
                && $dto->text === 'Описание дворников'
                && $dto->details === $details));

        /** @var VehicleWipersSheetImport $import */
        $import = app()->makeWith(VehicleWipersSheetImport::class, [
            'cacheKey' => 'vehicle_wipers_sheet_test',
            'lockKey' => 'vehicle_wipers_sheet_test_lock',
            'rowMapper' => $rowMapper,
        ]);

        $rows = new Collection([new Collection($row)]);

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
        $details = ['front' => ['adapter_type_front' => ['A1']]];
        $row = [
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
        ];
        $rowMapper = $this->rowMapper($row, $details);

        $wiperSpec = $this->mock(VehicleWiperSpecificationImportServiceInterface::class);
        $wiperSpec->shouldReceive('upsertFromRow')
            ->once()
            ->with(Mockery::on(fn (VehicleWiperSheetRowDTO $dto): bool => $dto->msId === 300))
            ->andThrow(new RuntimeException('ТС с ms_id 300 не найдено. Сначала импортируйте основной лист.'));

        /** @var VehicleWipersSheetImport $import */
        $import = app()->makeWith(VehicleWipersSheetImport::class, [
            'cacheKey' => 'vehicle_wipers_missing_vehicle_test',
            'lockKey' => 'vehicle_wipers_missing_vehicle_test_lock',
            'rowMapper' => $rowMapper,
        ]);

        $rows = new Collection([new Collection($row)]);

        $import->collection($rows);

        $this->assertSame(0, Vehicle::query()->count());
    }
}
