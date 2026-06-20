<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\UseCases\Vehicle\UpsertVehicleWiperSpecUseCase;
use App\Vehicles\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\FeatureValue;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use Mockery;
use Tests\TestCase;

final class UpsertVehicleWiperSpecUseCaseTest extends TestCase
{
    public function test_resolves_feature_value_by_name_and_upserts_spec(): void
    {
        $featureValue = new FeatureValue;
        $featureValue->id = 5;
        $expected = new PartSpecification;

        $featureValues = Mockery::mock(FeatureValueRepositoryInterface::class);
        $featureValues->shouldReceive('firstByName')->once()->with('Левый руль')->andReturn($featureValue);

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldReceive('upsert')
            ->once()
            ->with(Mockery::on(function (PartSpecificationData $data) {
                return $data->partableType === Vehicle::class
                    && $data->partableId === 77
                    && $data->template === DetailTemplateEnum::WIPER
                    && $data->details === ['len' => 600]
                    && $data->featureValueId === 5
                    && $data->name === 'имя'
                    && $data->text === 'текст';
            }))
            ->andReturn($expected);

        $useCase = new UpsertVehicleWiperSpecUseCase($featureValues, $partSpecs);

        $result = $useCase->execute(
            vehicleId: 77,
            templateSlug: DetailTemplateEnum::WIPER->value,
            details: ['len' => 600],
            featureValueName: 'Левый руль',
            name: 'имя',
            text: 'текст',
        );

        $this->assertSame($expected, $result);
    }

    public function test_null_feature_value_when_name_empty(): void
    {
        $featureValues = Mockery::mock(FeatureValueRepositoryInterface::class);
        $featureValues->shouldNotReceive('firstByName');

        $expected = new PartSpecification;
        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldReceive('upsert')
            ->once()
            ->with(Mockery::on(fn (PartSpecificationData $data) => $data->featureValueId === null))
            ->andReturn($expected);

        $useCase = new UpsertVehicleWiperSpecUseCase($featureValues, $partSpecs);

        $result = $useCase->execute(
            vehicleId: 77,
            templateSlug: DetailTemplateEnum::WIPER->value,
            details: [],
            featureValueName: null,
        );

        $this->assertSame($expected, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
