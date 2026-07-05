<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Domain\Models\Engine as PartableEngineType;
use App\Vehicles\Import\Application\Services\Engine\UpsertSparkPlugSpecByModificationService;
use App\Vehicles\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Import\Domain\ModelData\Modification\ModificationData;
use App\Vehicles\Import\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Import\Domain\ModelData\Vehicle\VehicleData;
use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Mockery;
use Tests\TestCase;

final class UpsertSparkPlugSpecByModificationServiceTest extends TestCase
{
    private function engine(int $id, string $code, ?EngineFuelTypeEnum $fuel): EngineData
    {
        return new EngineData(engId: $id * 1000, codeEngine: $code, engFuelType: $fuel, id: $id);
    }

    private function modification(array $engines): ModificationData
    {
        return new ModificationData(modId: 50, type: VehicleTypeEnum::PC, vehicleId: 9, msId: 200, engines: $engines);
    }

    public function test_writes_spec_only_for_engines_that_need_spark_plugs(): void
    {
        $mod = $this->modification([
            $this->engine(1, 'PETROL-1', EngineFuelTypeEnum::PETROL),
            $this->engine(2, 'DIESEL-1', EngineFuelTypeEnum::DIESEL),
        ]);

        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('firstByMsIdAndModIdWithEngines')->once()->with(200, 50)->andReturn($mod);

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('upsert')->once()
            ->with(Mockery::on(fn (PartSpecificationData $d) => $d->partableId === 1 && $d->partableType === PartableEngineType::class))
            ->andReturn(new PartSpecificationData(partableType: PartableEngineType::class, partableId: 1, template: DetailTemplateEnum::SPARK_PLUGS, details: []));

        $service = new UpsertSparkPlugSpecByModificationService(
            Mockery::mock(VehicleRepositoryInterface::class),
            $modifications,
            $command,
        );

        $result = $service->upsertByModification(200, 50, ['gap' => '0.9']);

        $this->assertTrue($result->found);
        $this->assertSame(1, $result->writtenCount);
        $this->assertSame([['code' => 'DIESEL-1', 'fuel' => EngineFuelTypeEnum::DIESEL->value]], $result->skippedEngines);
    }

    public function test_not_found_when_modification_missing(): void
    {
        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('firstByMsIdAndModIdWithEngines')->once()->with(200, 50)->andReturnNull();

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldNotReceive('upsert');

        $service = new UpsertSparkPlugSpecByModificationService(
            Mockery::mock(VehicleRepositoryInterface::class),
            $modifications,
            $command,
        );

        $result = $service->upsertByModification(200, 50, []);

        $this->assertFalse($result->found);
        $this->assertNotNull($result->notFoundReason);
    }

    public function test_negative_ms_id_resolves_parent(): void
    {
        $child = new VehicleData(
            msId: -5,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Child',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
        );

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('firstByMsId')->once()->with(-5)->andReturn($child);
        $vehicles->shouldReceive('parentMsId')->once()->with(-5)->andReturn(200);

        $mod = $this->modification([$this->engine(1, 'PETROL-1', EngineFuelTypeEnum::PETROL)]);
        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('firstByMsIdAndModIdWithEngines')->once()->with(200, 50)->andReturn($mod);

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('upsert')->once()->andReturn(new PartSpecificationData(partableType: PartableEngineType::class, partableId: 1, template: DetailTemplateEnum::SPARK_PLUGS, details: []));

        $service = new UpsertSparkPlugSpecByModificationService($vehicles, $modifications, $command);

        $result = $service->upsertByModification(-5, 50, []);

        $this->assertTrue($result->found);
        $this->assertSame(1, $result->writtenCount);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
