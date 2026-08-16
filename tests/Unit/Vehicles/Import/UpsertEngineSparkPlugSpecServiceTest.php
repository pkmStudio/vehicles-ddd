<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Application\Factories\PartSpecificationDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Engine\UpsertEngineSparkPlugSpecService;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\Contracts\Repositories\PartSpecificationDuplicateFinderInterface;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\PartSpecificationWritePolicy;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationCreated;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

final class UpsertEngineSparkPlugSpecServiceTest extends TestCase
{
    /**
     * Проверяет happy-path: двигатель находится по eng_id, спецификация свечей упсертится
     * с правильным partable_id/partable_type/template.
     *
     * Шаги:
     * 1. Мокает EngineRepositoryInterface::findByEngId — возвращает EngineData с id=42.
     * 2. Мокает PartSpecificationCommandInterface::create — ожидает данные с
     *    partableType=ENGINE, partableId=42, template=SPARK_PLUGS и переданными details.
     * 3. Зовёт upsertByEngine(101, $details).
     * 4. Проверяет, что вернулся именно ожидаемый результат Command.
     */
    public function test_resolves_engine_and_upserts_spec(): void
    {
        Event::fake([PartSpecificationCreated::class]);

        $engine = new EngineData(
            engId: 101,
            provider: ProviderEnum::TD,
            codeEngine: 'M54B30',
            powerKwStart: 170,
            powerPsStart: 231,
            fuelType: EngineFuelTypeEnum::PETROL,
            allowChangeFields: [],
            id: 42,
        );
        $details = ['gap' => '0.9'];
        $expected = new PartSpecificationData(
            partableType: PartableTypeEnum::ENGINE->value,
            partableId: 42,
            template: DetailTemplateEnum::SPARK_PLUGS,
            details: $details,
        );

        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('findByEngId')->once()->with(101)->andReturn($engine);

        $specifications = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specifications->shouldReceive('findByPartableTemplateAndFeatureValue')
            ->once()
            ->with(PartableTypeEnum::ENGINE->value, 42, DetailTemplateEnum::SPARK_PLUGS, null)
            ->andReturnNull();

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (PartSpecificationData $data) {
                return $data->partableType === PartableTypeEnum::ENGINE->value
                    && $data->partableId === 42
                    && $data->template === DetailTemplateEnum::SPARK_PLUGS
                    && $data->details === ['gap' => '0.9'];
            }))
            ->andReturn($expected);
        $duplicates = Mockery::mock(PartSpecificationDuplicateFinderInterface::class);
        $duplicates->shouldReceive('findDuplicate')->once()->andReturnNull();

        $service = new UpsertEngineSparkPlugSpecService(
            $engines,
            $partSpecs,
            $specifications,
            new PartSpecificationDataFactory,
            $duplicates,
            new PartSpecificationWritePolicy,
        );

        $this->assertSame($expected, $service->upsertByEngine(101, $details));
        Event::assertDispatched(PartSpecificationCreated::class);
    }

    /**
     * Проверяет защиту импорта от дубля owner/template/details до записи в command.
     */
    public function test_rejects_duplicate_owner_template_and_details(): void
    {
        $engine = new EngineData(
            engId: 101,
            provider: ProviderEnum::TD,
            codeEngine: 'M54B30',
            powerKwStart: 170,
            powerPsStart: 231,
            fuelType: EngineFuelTypeEnum::PETROL,
            allowChangeFields: [],
            id: 42,
        );

        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('findByEngId')->once()->with(101)->andReturn($engine);

        $specifications = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specifications->shouldReceive('findByPartableTemplateAndFeatureValue')
            ->once()
            ->with(PartableTypeEnum::ENGINE->value, 42, DetailTemplateEnum::SPARK_PLUGS, null)
            ->andReturnNull();

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldNotReceive('create');
        $partSpecs->shouldNotReceive('update');

        $duplicates = Mockery::mock(PartSpecificationDuplicateFinderInterface::class);
        $duplicates->shouldReceive('findDuplicate')
            ->once()
            ->andReturn(777);

        $service = new UpsertEngineSparkPlugSpecService(
            $engines,
            $partSpecs,
            $specifications,
            new PartSpecificationDataFactory,
            $duplicates,
            new PartSpecificationWritePolicy,
        );

        $this->expectException(ImportRowValidationException::class);
        $this->expectExceptionMessage('Спецификация с таким владельцем, шаблоном и details уже существует (ID=777).');

        $service->upsertByEngine(101, ['gap' => '0.9']);
    }

    /**
     * Проверяет, что при отсутствии двигателя с таким eng_id запись вообще не происходит.
     *
     * Шаги:
     * 1. Мокает Repository::findByEngId — возвращает null.
     * 2. Мокает Command — ожидает, что запись НЕ вызовется.
     * 3. Зовёт upsertByEngine(999, ...) и проверяет, что результат null.
     */
    public function test_returns_null_when_engine_not_found(): void
    {
        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('findByEngId')->once()->with(999)->andReturnNull();

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldNotReceive('create');
        $partSpecs->shouldNotReceive('update');

        $specifications = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specifications->shouldNotReceive('findByPartableTemplateAndFeatureValue');
        $duplicates = Mockery::mock(PartSpecificationDuplicateFinderInterface::class);
        $duplicates->shouldNotReceive('findDuplicate');

        $service = new UpsertEngineSparkPlugSpecService(
            $engines,
            $partSpecs,
            $specifications,
            new PartSpecificationDataFactory,
            $duplicates,
            new PartSpecificationWritePolicy,
        );

        $this->assertNull($service->upsertByEngine(999, ['gap' => '0.9']));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
