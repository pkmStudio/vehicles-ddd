<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\UseCases\Engine\UpsertSparkPlugSpecByModificationUseCase;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Models\Modification;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

final class UpsertSparkPlugSpecByModificationUseCaseTest extends TestCase
{
    private function engine(int $id, string $code, ?EngineFuelTypeEnum $fuel): Engine
    {
        $e = new Engine;
        $e->id = $id;
        $e->code_engine = $code;
        $e->setAttribute('eng_fuel_type', $fuel);

        return $e;
    }

    public function test_writes_spec_only_for_engines_that_need_spark_plugs(): void
    {
        $mod = new Modification;
        $mod->setRelation('engines', new Collection([
            $this->engine(1, 'PETROL-1', EngineFuelTypeEnum::PETROL),
            $this->engine(2, 'DIESEL-1', EngineFuelTypeEnum::DIESEL),
        ]));

        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('firstByMsIdAndModIdWithEngines')->once()->with(200, 50)->andReturn($mod);

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('upsert')->once()
            ->with(Mockery::on(fn (PartSpecificationData $d) => $d->partableId === 1 && $d->partableType === Engine::class))
            ->andReturn(new PartSpecification);

        $useCase = new UpsertSparkPlugSpecByModificationUseCase(
            Mockery::mock(VehicleRepositoryInterface::class),
            $modifications,
            $command,
        );

        $result = $useCase->execute(200, 50, ['gap' => '0.9']);

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

        $useCase = new UpsertSparkPlugSpecByModificationUseCase(
            Mockery::mock(VehicleRepositoryInterface::class),
            $modifications,
            $command,
        );

        $result = $useCase->execute(200, 50, []);

        $this->assertFalse($result->found);
        $this->assertNotNull($result->notFoundReason);
    }

    public function test_negative_ms_id_resolves_parent(): void
    {
        $parent = new Vehicle;
        $parent->ms_id = 200;
        $child = new Vehicle;
        $child->setRelation('parent', $parent);

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('firstByMsId')->once()->with(-5)->andReturn($child);

        $mod = new Modification;
        $mod->setRelation('engines', new Collection([$this->engine(1, 'PETROL-1', EngineFuelTypeEnum::PETROL)]));
        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('firstByMsIdAndModIdWithEngines')->once()->with(200, 50)->andReturn($mod);

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('upsert')->once()->andReturn(new PartSpecification);

        $useCase = new UpsertSparkPlugSpecByModificationUseCase($vehicles, $modifications, $command);

        $result = $useCase->execute(-5, 50, []);

        $this->assertTrue($result->found);
        $this->assertSame(1, $result->writtenCount);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
