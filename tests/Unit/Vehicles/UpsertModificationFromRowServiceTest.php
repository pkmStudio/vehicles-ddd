<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Import\Application\Factories\ModificationDataFactory;
use App\Vehicles\Import\Application\Services\Modification\UpsertModificationFromRowService;
use App\Vehicles\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\DTOs\Modification\ModificationCommandRowDTO;
use App\Vehicles\Import\Domain\ModelData\ModificationData;
use App\Vehicles\Import\Domain\ModelData\VehicleData;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Mockery;
use Tests\TestCase;

final class UpsertModificationFromRowServiceTest extends TestCase
{
    public function test_resolves_vehicle_and_upserts_modification(): void
    {
        $vehicle = new VehicleData(
            msId: 200,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Octavia',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
            provider: ProviderEnum::TD,
            id: 9,
        );
        $expected = new ModificationData(modId: 50, type: VehicleTypeEnum::PC, vehicleId: 9, msId: 200);

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('firstByMsId')->once()->with(200)->andReturn($vehicle);

        $command = Mockery::mock(ModificationCommandInterface::class);
        $command->shouldReceive('upsertByModIdAndType')
            ->once()
            ->with(Mockery::on(fn (ModificationData $d) => $d->modId === 50 && $d->msId === 200 && $d->type === VehicleTypeEnum::PC && $d->vehicleId === 9))
            ->andReturn($expected);

        $service = new UpsertModificationFromRowService($command, new ModificationDataFactory, $vehicles);

        $this->assertSame($expected, $service->upsertFromRow($this->validRow()));
    }

    public function test_returns_null_when_vehicle_not_found(): void
    {
        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('firstByMsId')->once()->with(200)->andReturnNull();

        $command = Mockery::mock(ModificationCommandInterface::class);
        $command->shouldNotReceive('upsertByModIdAndType');

        $service = new UpsertModificationFromRowService($command, new ModificationDataFactory, $vehicles);

        $this->assertNull($service->upsertFromRow($this->validRow()));
    }

    private function validRow(): ModificationCommandRowDTO
    {
        return new ModificationCommandRowDTO(
            msId: 200,
            modId: 50,
            yearFrom: null,
            yearTo: null,
            description: null,
            powerPs: null,
            powerKw: null,
            engineType: null,
            gearType: null,
            driveType: null,
            brakeSystemType: null,
            numberOfCylinders: null,
            capacityLt: null,
            type: 'PC',
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
