<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Application\Factories\PartSpecificationDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Engine\UpsertEngineSparkPlugSpecService;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Mockery;
use Tests\TestCase;

final class UpsertEngineSparkPlugSpecServiceTest extends TestCase
{
    /**
     * Проверяет happy-path: двигатель находится по eng_id, спецификация свечей упсертится
     * с правильным partable_id/partable_type/template.
     *
     * Шаги:
     * 1. Мокает EngineRepositoryInterface::firstByEngId — возвращает EngineData с id=42.
     * 2. Мокает PartSpecificationCommandInterface::upsert — ожидает данные с
     *    partableType=ENGINE, partableId=42, template=SPARK_PLUGS и переданными details.
     * 3. Зовёт upsertByEngine(101, $details).
     * 4. Проверяет, что вернулся именно ожидаемый результат Command.
     */
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

        $service = new UpsertEngineSparkPlugSpecService($engines, $partSpecs, new PartSpecificationDataFactory);

        $this->assertSame($expected, $service->upsertByEngine(101, $details));
    }

    /**
     * Проверяет, что при отсутствии двигателя с таким eng_id запись вообще не происходит.
     *
     * Шаги:
     * 1. Мокает Repository::firstByEngId — возвращает null.
     * 2. Мокает Command — ожидает, что upsert НЕ вызовется.
     * 3. Зовёт upsertByEngine(999, ...) и проверяет, что результат null.
     */
    public function test_returns_null_when_engine_not_found(): void
    {
        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByEngId')->once()->with(999)->andReturnNull();

        $partSpecs = Mockery::mock(PartSpecificationCommandInterface::class);
        $partSpecs->shouldNotReceive('upsert');

        $service = new UpsertEngineSparkPlugSpecService($engines, $partSpecs, new PartSpecificationDataFactory);

        $this->assertNull($service->upsertByEngine(999, ['gap' => '0.9']));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
