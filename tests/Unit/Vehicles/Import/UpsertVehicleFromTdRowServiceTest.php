<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Application\Factories\VehicleDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Vehicle\UpsertVehicleFromTdRowService;
use App\Modules\Vehicles\Features\Import\Application\Services\Vehicle\VehicleImportWritePolicy;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;
use Illuminate\Support\Facades\Event;
use Mockery;
use Psr\Log\NullLogger;
use Tests\TestCase;

final class UpsertVehicleFromTdRowServiceTest extends TestCase
{
    /**
     * Проверяет happy-path: производитель находится по mfa_id из строки, ТС маппится с его
     * id как manufacturerId и уходит в Command.
     *
     * Шаги:
     * 1. Мокает ManufacturerRepositoryInterface::findByMfaId — возвращает ManufacturerData(id=3).
     * 2. Мокает Command::create — ожидает данные с manufacturerId=3 и остальными полями
     *    строки (msId/name/type).
     * 3. Зовёт upsertFromRow() с валидным VehicleTdRowDTO.
     * 4. Проверяет, что вернулся именно ожидаемый результат Command.
     */
    public function test_resolves_manufacturer_and_upserts_vehicle(): void
    {
        Event::fake([VehicleCreated::class]);

        $manufacturer = new ManufacturerData(mfaId: 10, name: 'Skoda', provider: ProviderEnum::TD, id: 3);
        $expected = new VehicleData(
            msId: 200,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Octavia',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
            provider: ProviderEnum::TD,
            generation: 'A7',
            generationYearFrom: 2013,
        );

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('findByMfaId')->once()->with(10)->andReturn($manufacturer);

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('findByMsId')->once()->with(200)->andReturnNull();

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (VehicleData $data) {
                return $data->msId === 200
                    && $data->mfaId === 10
                    && $data->manufacturerId === 3
                    && $data->name === 'Octavia'
                    && $data->type === VehicleTypeEnum::PC;
            }))
            ->andReturn($expected);

        $service = new UpsertVehicleFromTdRowService(
            $command,
            new VehicleDataFactory,
            $manufacturers,
            $vehicles,
            new VehicleImportWritePolicy(new NullLogger),
        );

        $this->assertSame($expected, $service->upsertFromRow($this->validRow()));

        Event::assertDispatched(VehicleCreated::class);
    }

    /**
     * Проверяет, что при отсутствии производителя с таким mfa_id ТС не записывается —
     * нет смысла заводить ТС без родителя-производителя.
     *
     * Шаги:
     * 1. Мокает ManufacturerRepositoryInterface::findByMfaId — возвращает null.
     * 2. Мокает Command — ожидает, что create НЕ вызовется.
     * 3. Зовёт upsertFromRow() и проверяет, что результат null.
     */
    public function test_returns_null_when_manufacturer_not_found(): void
    {
        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('findByMfaId')->once()->with(10)->andReturnNull();

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldNotReceive('create');

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldNotReceive('findByMsId');

        $service = new UpsertVehicleFromTdRowService(
            $command,
            new VehicleDataFactory,
            $manufacturers,
            $vehicles,
            new VehicleImportWritePolicy(new NullLogger),
        );

        $this->assertNull($service->upsertFromRow($this->validRow()));
    }

    /**
     * Проверяет бизнес-правило дефолта: если тип ТС — мотоцикл (MB) и тип кузова в строке не
     * указан, фабрика подставляет CarcaseTypeEnum::MOTORCYCLE, а не оставляет null.
     *
     * Шаги:
     * 1. Мокает ManufacturerRepositoryInterface — возвращает ManufacturerData.
     * 2. Мокает Command::create — просто возвращает переданный ему VehicleData как есть.
     * 3. Зовёт upsertFromRow() со строкой type='MB', typeCarcase=null.
     * 4. Проверяет, что итоговый VehicleData имеет type=MB и typeCarcase=MOTORCYCLE.
     */
    public function test_defaults_type_carcase_to_motorcycle_when_missing_for_mb_type(): void
    {
        /** [mfa_id, ms_id, name, generation, type_carcase, year_from, year_to, type] */
        $row = new VehicleTdRowDTO(
            mfaId: 10,
            msId: 200,
            name: 'Ninja',
            generation: 'Ninja',
            typeCarcase: null,
            generationYearFrom: 2013,
            generationYearTo: 2020,
            type: 'MB',
        );

        $manufacturer = new ManufacturerData(mfaId: 10, name: 'Kawasaki', provider: ProviderEnum::TD, id: 3);

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('findByMfaId')->once()->with(10)->andReturn($manufacturer);

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('findByMsId')->once()->with(200)->andReturnNull();

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->andReturnUsing(fn (VehicleData $data) => $data);

        $service = new UpsertVehicleFromTdRowService(
            $command,
            new VehicleDataFactory,
            $manufacturers,
            $vehicles,
            new VehicleImportWritePolicy(new NullLogger),
        );

        $data = $service->upsertFromRow($row);

        $this->assertSame(VehicleTypeEnum::MB, $data->type);
        $this->assertSame(CarcaseTypeEnum::MOTORCYCLE, $data->typeCarcase);
    }

    public function test_tecdoc_import_overwrites_existing_vehicle_as_authoritative_source(): void
    {
        Event::fake([VehicleUpdated::class]);

        $manufacturer = new ManufacturerData(mfaId: 10, name: 'Skoda', provider: ProviderEnum::TD, id: 3);
        $existing = new VehicleData(
            msId: 200,
            mfaId: 99,
            manufacturerId: 9,
            name: 'OD old name',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::SALOON,
            provider: ProviderEnum::OD,
            generation: 'OD generation',
            generationYearFrom: 2010,
            generationYearTo: 2012,
            parentId: 77,
            id: 5,
        );
        $updated = new VehicleData(
            msId: 200,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Octavia',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
            provider: ProviderEnum::TD,
            generation: 'A7',
            generationYearFrom: 2013,
            generationYearTo: 2020,
            id: 5,
        );

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('findByMfaId')->once()->with(10)->andReturn($manufacturer);

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('findByMsId')->once()->with(200)->andReturn($existing);

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldReceive('updateByMsId')
            ->once()
            ->with(Mockery::on(function (VehicleData $data): bool {
                return $data->msId === 200
                    && $data->mfaId === 10
                    && $data->manufacturerId === 3
                    && $data->name === 'Octavia'
                    && $data->typeCarcase === CarcaseTypeEnum::HATCHBACK
                    && $data->provider === ProviderEnum::TD
                    && $data->generation === 'A7'
                    && $data->generationYearFrom === 2013
                    && $data->parentId === null;
            }))
            ->andReturn($updated);

        $service = new UpsertVehicleFromTdRowService(
            $command,
            new VehicleDataFactory,
            $manufacturers,
            $vehicles,
            new VehicleImportWritePolicy(new NullLogger),
        );

        $this->assertSame($updated, $service->upsertFromRow($this->validRow()));
    }

    private function validRow(): VehicleTdRowDTO
    {
        return new VehicleTdRowDTO(
            mfaId: 10,
            msId: 200,
            name: 'Octavia',
            generation: 'A7',
            typeCarcase: 'Hatchback',
            generationYearFrom: 2013,
            generationYearTo: 2020,
            type: 'PC',
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
