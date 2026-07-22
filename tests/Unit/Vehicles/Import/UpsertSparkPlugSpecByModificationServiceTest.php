<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Application\Factories\PartSpecificationDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Engine\UpsertSparkPlugSpecByModificationService;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationCreated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

final class UpsertSparkPlugSpecByModificationServiceTest extends TestCase
{
    private function engine(int $id, string $code, ?EngineFuelTypeEnum $fuel): EngineData
    {
        return new EngineData(engId: $id * 1000, codeEngine: $code, engFuelType: $fuel, id: $id);
    }

    /** @param  array<EngineData>  $engines */
    private function modification(array $engines): ModificationData
    {
        return new ModificationData(modId: 50, type: VehicleTypeEnum::PC, vehicleId: 9, msId: 200, engines: new Collection($engines));
    }

    /**
     * Проверяет бизнес-правило: свечи зажигания актуальны только для бензиновых двигателей —
     * из всех двигателей модификации спецификация пишется только на PETROL, дизельный
     * двигатель пропускается и попадает в отчёт skippedEngines.
     *
     * Шаги:
     * 1. Собирает модификацию с двумя двигателями: PETROL и DIESEL.
     * 2. Мокает ModificationRepositoryInterface::findByMsIdAndModIdWithEngines — возвращает
     *    эту модификацию с уже eager-loaded двигателями.
     * 3. Мокает PartSpecificationCommandInterface::create — ожидает вызов только для
     *    PETROL-двигателя (partableId=1).
     * 4. Зовёт upsertByModification(200, 50, $details).
     * 5. Проверяет found=true, writtenCount=1 и что DIESEL-двигатель попал в skippedEngines.
     */
    public function test_writes_spec_only_for_engines_that_need_spark_plugs(): void
    {
        Event::fake([PartSpecificationCreated::class]);

        $mod = $this->modification([
            $this->engine(1, 'PETROL-1', EngineFuelTypeEnum::PETROL),
            $this->engine(2, 'DIESEL-1', EngineFuelTypeEnum::DIESEL),
        ]);

        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('findByMsIdAndModIdWithEngines')->once()->with(200, 50)->andReturn($mod);

        $specifications = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specifications->shouldReceive('findByPartableTemplateAndFeatureValue')
            ->once()
            ->with(PartableTypeEnum::ENGINE->value, 1, DetailTemplateEnum::SPARK_PLUGS, null)
            ->andReturnNull();

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->once()
            ->with(Mockery::on(fn (PartSpecificationData $d) => $d->partableId === 1 && $d->partableType === PartableTypeEnum::ENGINE->value))
            ->andReturn(new PartSpecificationData(partableType: PartableTypeEnum::ENGINE->value, partableId: 1, template: DetailTemplateEnum::SPARK_PLUGS, details: []));

        $service = new UpsertSparkPlugSpecByModificationService(
            Mockery::mock(VehicleRepositoryInterface::class),
            $modifications,
            $command,
            $specifications,
            new PartSpecificationDataFactory,
        );

        $result = $service->upsertByModification(200, 50, ['gap' => '0.9']);

        $this->assertTrue($result->found);
        $this->assertSame(1, $result->writtenCount);
        $this->assertSame([['code' => 'DIESEL-1', 'fuel' => EngineFuelTypeEnum::DIESEL->value]], $result->skippedEngines);
        Event::assertDispatched(PartSpecificationCreated::class);
    }

    /**
     * Проверяет, что при отсутствии модификации по ms_id/mod_id запись не происходит и
     * причина ненахождения явно указана в результате.
     *
     * Шаги:
     * 1. Мокает ModificationRepositoryInterface — возвращает null.
     * 2. Мокает Command — ожидает, что запись НЕ вызовется.
     * 3. Зовёт upsertByModification(200, 50, []).
     * 4. Проверяет found=false и что notFoundReason заполнен.
     */
    public function test_not_found_when_modification_missing(): void
    {
        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('findByMsIdAndModIdWithEngines')->once()->with(200, 50)->andReturnNull();

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldNotReceive('create');
        $command->shouldNotReceive('update');

        $specifications = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specifications->shouldNotReceive('findByPartableTemplateAndFeatureValue');

        $service = new UpsertSparkPlugSpecByModificationService(
            Mockery::mock(VehicleRepositoryInterface::class),
            $modifications,
            $command,
            $specifications,
            new PartSpecificationDataFactory,
        );

        $result = $service->upsertByModification(200, 50, []);

        $this->assertFalse($result->found);
        $this->assertNotNull($result->notFoundReason);
    }

    /**
     * Проверяет бизнес-правило синтетических отрицательных ms_id (генерируются при импорте
     * ТС без собственного ms_id): для такого «дочернего» ТС модификация ищется по ms_id
     * родителя, а не по самому отрицательному значению.
     *
     * Шаги:
     * 1. Мокает VehicleRepositoryInterface: findByMsId(-5) возвращает дочернее ТС,
     *    parentMsId(-5) возвращает 200 (ms_id родителя).
     * 2. Мокает ModificationRepositoryInterface::findByMsIdAndModIdWithEngines — ожидает
     *    вызов именно с (200, 50), не (-5, 50).
     * 3. Зовёт upsertByModification(-5, 50, []).
     * 4. Проверяет found=true и writtenCount=1.
     */
    public function test_negative_ms_id_resolves_parent(): void
    {
        $child = new VehicleData(
            msId: -5,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Child',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
            provider: ProviderEnum::TD,
        );

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('findByMsId')->once()->with(-5)->andReturn($child);
        $vehicles->shouldReceive('parentMsId')->once()->with(-5)->andReturn(200);

        $mod = $this->modification([$this->engine(1, 'PETROL-1', EngineFuelTypeEnum::PETROL)]);
        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('findByMsIdAndModIdWithEngines')->once()->with(200, 50)->andReturn($mod);

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->once()->andReturn(new PartSpecificationData(partableType: PartableTypeEnum::ENGINE->value, partableId: 1, template: DetailTemplateEnum::SPARK_PLUGS, details: []));

        $specifications = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specifications->shouldReceive('findByPartableTemplateAndFeatureValue')
            ->once()
            ->with(PartableTypeEnum::ENGINE->value, 1, DetailTemplateEnum::SPARK_PLUGS, null)
            ->andReturnNull();

        $service = new UpsertSparkPlugSpecByModificationService($vehicles, $modifications, $command, $specifications, new PartSpecificationDataFactory);

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
