<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Import\Application\Services\Engine\UpsertEngineSparkPlugSpecService;
use App\Vehicles\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Import\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Import\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Mockery;
use Tests\TestCase;

final class UpsertEngineSparkPlugSpecServiceTest extends TestCase
{
    public function test_resolves_engine_and_upserts_spec(): void
    {
        $engine = new EngineData(engId: 101, id: 42);
        $details = ['gap' => '0.9'];
        $expected = new PartSpecificationData(
            partableType: PartableTypeEnum::ENGINE->value,
            partableId: 42,
            template: DetailTemplateEnum::SPARK_PLUGS,
            details: $details,
        );

        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByEngId')->once()->with(101)->andReturn($engine);

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldReceive('upsert')
            ->once()
            ->with(Mockery::on(function (PartSpecificationData $data) {
                return $data->partableType === PartableTypeEnum::ENGINE->value
                    && $data->partableId === 42
                    && $data->template === DetailTemplateEnum::SPARK_PLUGS
                    && $data->details === ['gap' => '0.9'];
            }))
            ->andReturn($expected);

        $service = new UpsertEngineSparkPlugSpecService($engines, $partSpecs);

        $this->assertSame($expected, $service->upsertByEngine(101, $details));
    }

    public function test_returns_null_when_engine_not_found(): void
    {
        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByEngId')->once()->with(999)->andReturnNull();

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldNotReceive('upsert');

        $service = new UpsertEngineSparkPlugSpecService($engines, $partSpecs);

        $this->assertNull($service->upsertByEngine(999, ['gap' => '0.9']));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
