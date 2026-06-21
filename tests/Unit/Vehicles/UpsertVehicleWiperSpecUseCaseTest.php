<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Common\Services\WiperSpecificationService;
use App\Vehicles\Application\Import\UseCases\Vehicle\UpsertVehicleWiperSpecUseCase;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\FeatureValue;
use App\Vehicles\Domain\Models\PartSpecification;
use Mockery;
use Tests\TestCase;

final class UpsertVehicleWiperSpecUseCaseTest extends TestCase
{
    private function useCase(
        PartSpecificationRepositoryInterface $specs,
        PartSpecificationCommandInterface $command,
        ?FeatureValueRepositoryInterface $featureValues = null,
    ): UpsertVehicleWiperSpecUseCase {
        return new UpsertVehicleWiperSpecUseCase(
            $featureValues ?? Mockery::mock(FeatureValueRepositoryInterface::class),
            $specs,
            $command,
            new WiperSpecificationService,
        );
    }

    public function test_creates_one_spec_per_side_when_none_exist(): void
    {
        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front')->andReturnNull();
        $specs->shouldReceive('firstByVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'back')->andReturnNull();

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

        $this->useCase($specs, $command)->execute(77, DetailTemplateEnum::WIPER->value, $details);

        $this->assertSame(['front', 'back'], $created);
    }

    public function test_updates_existing_side(): void
    {
        $existing = new PartSpecification;

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front')->andReturn($existing);

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('update')->once()
            ->with($existing, Mockery::type(PartSpecificationData::class))->andReturn($existing);
        $command->shouldNotReceive('create');

        // только front
        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->useCase($specs, $command)->execute(77, DetailTemplateEnum::WIPER->value, $details);

        $this->addToAssertionCount(1);
    }

    public function test_resolves_feature_value_by_name(): void
    {
        $fv = new FeatureValue;
        $fv->id = 9;
        $featureValues = Mockery::mock(FeatureValueRepositoryInterface::class);
        $featureValues->shouldReceive('firstByName')->once()->with('Левый руль')->andReturn($fv);

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateAndSide')->once()->andReturnNull();

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->once()
            ->with(Mockery::on(fn (PartSpecificationData $d) => $d->featureValueId === 9))
            ->andReturn(new PartSpecification);

        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->useCase($specs, $command, $featureValues)
            ->execute(77, DetailTemplateEnum::WIPER->value, $details, featureValueName: 'Левый руль');

        $this->addToAssertionCount(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
