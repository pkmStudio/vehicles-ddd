<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\Factories\Vehicle\VehicleDataFactory;
use App\Vehicles\Application\Import\Services\Vehicle\UpsertVehicleFromTdRowService;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\VehicleCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Domain\ModelData\Vehicle\VehicleData;
use App\Vehicles\Domain\Models\Manufacturer;
use App\Vehicles\Domain\Models\Vehicle;
use Mockery;
use Tests\TestCase;

final class UpsertVehicleFromTdRowServiceTest extends TestCase
{
    /** [mfa_id, ms_id, name, generation, type_carcase, year_from, year_to, type] */
    private const VALID_ROW = [10, 200, 'Octavia', 'A7', null, 2013, 2020, 'PC'];

    public function test_resolves_manufacturer_and_upserts_vehicle(): void
    {
        $manufacturer = new Manufacturer;
        $manufacturer->id = 3;
        $expected = new Vehicle;

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('firstByMfaId')->once()->with(10)->andReturn($manufacturer);

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldReceive('upsertByMsId')
            ->once()
            ->with(Mockery::on(function (VehicleData $data) {
                return $data->msId === 200
                    && $data->mfaId === 10
                    && $data->manufacturerId === 3
                    && $data->name === 'Octavia'
                    && $data->type === 'PC';
            }))
            ->andReturn($expected);

        $service = new UpsertVehicleFromTdRowService($command, new VehicleDataFactory, $manufacturers);

        $this->assertSame($expected, $service->upsertFromRow(self::VALID_ROW));
    }

    public function test_returns_null_when_manufacturer_not_found(): void
    {
        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('firstByMfaId')->once()->with(10)->andReturnNull();

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldNotReceive('upsertByMsId');

        $service = new UpsertVehicleFromTdRowService($command, new VehicleDataFactory, $manufacturers);

        $this->assertNull($service->upsertFromRow(self::VALID_ROW));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
