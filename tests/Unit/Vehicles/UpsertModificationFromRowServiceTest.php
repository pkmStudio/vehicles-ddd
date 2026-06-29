<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\Factories\Modification\ModificationDataFactory;
use App\Vehicles\Application\Import\Services\Modification\UpsertModificationFromRowService;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\ModificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\ModelData\Modification\ModificationData;
use App\Vehicles\Domain\Models\Modification;
use App\Vehicles\Domain\Models\Vehicle;
use Mockery;
use Tests\TestCase;

final class UpsertModificationFromRowServiceTest extends TestCase
{
    /** [ms_id, mod_id, year_from, year_to, descr, ps, kw, eng, gear, drive, brake, cyl, cap, type] */
    private const VALID_ROW = [200, 50, null, null, null, null, null, null, null, null, null, null, null, 'PC'];

    public function test_resolves_vehicle_and_upserts_modification(): void
    {
        $vehicle = new Vehicle;
        $vehicle->id = 9;
        $expected = new Modification;

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('firstByMsId')->once()->with(200)->andReturn($vehicle);

        $command = Mockery::mock(ModificationCommandInterface::class);
        $command->shouldReceive('upsertByModIdAndType')
            ->once()
            ->with(Mockery::on(fn (ModificationData $d) => $d->modId === 50 && $d->msId === 200 && $d->type === 'PC' && $d->vehicleId === 9))
            ->andReturn($expected);

        $service = new UpsertModificationFromRowService($command, new ModificationDataFactory, $vehicles);

        $this->assertSame($expected, $service->execute(self::VALID_ROW));
    }

    public function test_returns_null_when_vehicle_not_found(): void
    {
        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('firstByMsId')->once()->with(200)->andReturnNull();

        $command = Mockery::mock(ModificationCommandInterface::class);
        $command->shouldNotReceive('upsertByModIdAndType');

        $service = new UpsertModificationFromRowService($command, new ModificationDataFactory, $vehicles);

        $this->assertNull($service->execute(self::VALID_ROW));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
