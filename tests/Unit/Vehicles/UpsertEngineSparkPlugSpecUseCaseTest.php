<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\UseCases\Engine\UpsertEngineSparkPlugSpecUseCase;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\EngineRepositoryInterface;
use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Models\PartSpecification;
use Mockery;
use Tests\TestCase;

final class UpsertEngineSparkPlugSpecUseCaseTest extends TestCase
{
    public function test_resolves_engine_and_upserts_spec(): void
    {
        $engine = new Engine;
        $engine->id = 42;
        $details = ['gap' => '0.9'];
        $expected = new PartSpecification;

        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByEngId')->once()->with(101)->andReturn($engine);

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldReceive('upsert')
            ->once()
            ->with(Mockery::on(function (PartSpecificationData $data) {
                return $data->partableType === Engine::class
                    && $data->partableId === 42
                    && $data->template === DetailTemplateEnum::SPARK_PLUGS
                    && $data->details === ['gap' => '0.9'];
            }))
            ->andReturn($expected);

        $useCase = new UpsertEngineSparkPlugSpecUseCase($engines, $partSpecs);

        $this->assertSame($expected, $useCase->execute(101, $details));
    }

    public function test_returns_null_when_engine_not_found(): void
    {
        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByEngId')->once()->with(999)->andReturnNull();

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldNotReceive('upsert');

        $useCase = new UpsertEngineSparkPlugSpecUseCase($engines, $partSpecs);

        $this->assertNull($useCase->execute(999, ['gap' => '0.9']));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
