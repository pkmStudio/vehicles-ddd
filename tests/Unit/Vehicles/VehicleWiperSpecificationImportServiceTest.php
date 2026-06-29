<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Common\Services\WiperSpecificationService;
use App\Vehicles\Application\Import\Services\Vehicle\VehicleWiperSpecificationImportService;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\FeatureValue;
use App\Vehicles\Domain\Models\PartSpecification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\TestCase;

final class VehicleWiperSpecificationImportServiceTest extends TestCase
{
    private function service(
        PartSpecificationRepositoryInterface $specs,
        PartSpecificationCommandInterface $command,
        ?FeatureValueRepositoryInterface $featureValues = null,
    ): VehicleWiperSpecificationImportService {
        return new VehicleWiperSpecificationImportService(
            $featureValues ?? Mockery::mock(FeatureValueRepositoryInterface::class),
            $specs,
            $command,
            new WiperSpecificationService,
        );
    }

    public function test_creates_one_spec_per_side_when_none_exist(): void
    {
        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A1'], 'count_wipers' => 2]])
            ->andReturnNull();
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'back', ['back' => ['adapter_type_rear' => ['B1']]])
            ->andReturnNull();
        $specs->shouldReceive('forVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front')
            ->andReturn(new EloquentCollection);
        $specs->shouldReceive('forVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'back')
            ->andReturn(new EloquentCollection);

        $created = [];
        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->twice()
            ->with(Mockery::on(function (PartSpecificationData $d) use (&$created) {
                $created[] = array_key_first($d->details);

                return true;
            }))
            ->andReturn(new PartSpecification);
        $command->shouldNotReceive('update');

        $details = [
            'front' => ['adapter_type_front' => ['A1'], 'count_wipers' => 2],
            'back' => ['adapter_type_rear' => ['B1']],
        ];

        $this->service($specs, $command)->execute(77, DetailTemplateEnum::WIPER->value, $details);

        $this->assertSame(['front', 'back'], $created);
    }

    public function test_updates_existing_side(): void
    {
        $existing = new PartSpecification;
        $existing->details = ['front' => ['adapter_type_front' => ['A1']]];

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A1']]])
            ->andReturn($existing);
        $specs->shouldNotReceive('forVehicleTemplateAndSide');

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('update')->once()
            ->with($existing, Mockery::type(PartSpecificationData::class))->andReturn($existing);
        $command->shouldNotReceive('create');

        // только front
        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->service($specs, $command)->execute(77, DetailTemplateEnum::WIPER->value, $details);

        $this->addToAssertionCount(1);
    }

    public function test_updates_single_existing_side_when_exact_details_are_missing(): void
    {
        $existing = new PartSpecification;
        $existing->id = 5;
        $existing->details = ['front' => ['adapter_type_front' => ['OLD']]];

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A1']]])
            ->andReturnNull();
        $specs->shouldReceive('forVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front')
            ->andReturn(new EloquentCollection([$existing]));

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('update')->once()
            ->with($existing, Mockery::type(PartSpecificationData::class))
            ->andReturn($existing);
        $command->shouldNotReceive('create');

        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->service($specs, $command)->execute(77, DetailTemplateEnum::WIPER->value, $details);

        $this->addToAssertionCount(1);
    }

    public function test_resolves_feature_value_by_name(): void
    {
        $fv = new FeatureValue;
        $fv->id = 9;
        $featureValues = Mockery::mock(FeatureValueRepositoryInterface::class);
        $featureValues->shouldReceive('firstByName')->once()->with('Левый руль')->andReturn($fv);

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()->andReturnNull();
        $specs->shouldReceive('forVehicleTemplateAndSide')->once()->andReturn(new EloquentCollection);

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->once()
            ->with(Mockery::on(fn (PartSpecificationData $d) => $d->featureValueId === 9))
            ->andReturn(new PartSpecification);

        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->service($specs, $command, $featureValues)
            ->execute(77, DetailTemplateEnum::WIPER->value, $details, featureValueName: 'Левый руль');

        $this->addToAssertionCount(1);
    }

    public function test_creates_separate_specs_for_multiple_front_adapters_without_fallback_update(): void
    {
        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A1'], 'count_wipers' => 2]])
            ->andReturnNull();
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A2'], 'count_wipers' => 2]])
            ->andReturnNull();
        $specs->shouldNotReceive('forVehicleTemplateAndSide');

        $createdAdapters = [];
        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->twice()
            ->with(Mockery::on(function (PartSpecificationData $data) use (&$createdAdapters): bool {
                $createdAdapters[] = $data->details['front']['adapter_type_front'];

                return true;
            }))
            ->andReturn(new PartSpecification);
        $command->shouldNotReceive('update');

        $details = ['front' => ['adapter_type_front' => ['A1', 'A2'], 'count_wipers' => 2]];

        $this->service($specs, $command)->execute(77, DetailTemplateEnum::WIPER->value, $details);

        $this->assertSame([['A1'], ['A2']], $createdAdapters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
