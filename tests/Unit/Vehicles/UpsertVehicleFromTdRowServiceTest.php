<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Import\Application\Factories\VehicleDataFactory;
use App\Vehicles\Import\Application\Services\Vehicle\UpsertVehicleFromTdRowService;
use App\Vehicles\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Vehicles\Import\Domain\ModelData\ManufacturerData;
use App\Vehicles\Import\Domain\ModelData\VehicleData;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Mockery;
use Tests\TestCase;

final class UpsertVehicleFromTdRowServiceTest extends TestCase
{
    public function test_resolves_manufacturer_and_upserts_vehicle(): void
    {
        $manufacturer = new ManufacturerData(mfaId: 10, name: 'Skoda', provider: ProviderEnum::TD, id: 3);
        $expected = new VehicleData(
            msId: 200,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Octavia',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
            provider: ProviderEnum::TD,
        );

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
                    && $data->type === VehicleTypeEnum::PC;
            }))
            ->andReturn($expected);

        $service = new UpsertVehicleFromTdRowService($command, new VehicleDataFactory, $manufacturers);

        $this->assertSame($expected, $service->upsertFromRow($this->validRow()));
    }

    public function test_returns_null_when_manufacturer_not_found(): void
    {
        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('firstByMfaId')->once()->with(10)->andReturnNull();

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldNotReceive('upsertByMsId');

        $service = new UpsertVehicleFromTdRowService($command, new VehicleDataFactory, $manufacturers);

        $this->assertNull($service->upsertFromRow($this->validRow()));
    }

    public function test_defaults_type_carcase_to_motorcycle_when_missing_for_mb_type(): void
    {
        /** [mfa_id, ms_id, name, generation, type_carcase, year_from, year_to, type] */
        $row = new VehicleTdRowDTO(
            mfaId: 10,
            msId: 200,
            name: 'Ninja',
            generation: null,
            typeCarcase: null,
            generationYearFrom: 2013,
            generationYearTo: 2020,
            type: 'MB',
        );

        $manufacturer = new ManufacturerData(mfaId: 10, name: 'Kawasaki', provider: ProviderEnum::TD, id: 3);

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('firstByMfaId')->once()->with(10)->andReturn($manufacturer);

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldReceive('upsertByMsId')
            ->once()
            ->andReturnUsing(fn (VehicleData $data) => $data);

        $service = new UpsertVehicleFromTdRowService($command, new VehicleDataFactory, $manufacturers);

        $data = $service->upsertFromRow($row);

        $this->assertSame(VehicleTypeEnum::MB, $data->type);
        $this->assertSame(CarcaseTypeEnum::MOTORCYCLE, $data->typeCarcase);
    }

    private function validRow(): VehicleTdRowDTO
    {
        return new VehicleTdRowDTO(
            mfaId: 10,
            msId: 200,
            name: 'Octavia',
            generation: 'A7',
            typeCarcase: 'Hatchback',
            generationYearFrom: 2013,
            generationYearTo: 2020,
            type: 'PC',
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
