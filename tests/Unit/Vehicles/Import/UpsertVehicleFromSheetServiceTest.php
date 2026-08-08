<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Application\Factories\ManufacturerDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Factories\VehicleDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Vehicle\UpsertVehicleFromSheetService;
use App\Modules\Vehicles\Features\Import\Application\Services\Vehicle\VehicleImportWritePolicy;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
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

final class UpsertVehicleFromSheetServiceTest extends TestCase
{
    public function test_creates_vehicle_with_provider_from_sheet_row(): void
    {
        Event::fake([VehicleCreated::class]);

        $manufacturer = new ManufacturerData(
            mfaId: 10,
            name: 'Skoda',
            provider: ProviderEnum::OD,
            id: 3,
        );
        $expected = new VehicleData(
            msId: 200,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Octavia',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
            provider: ProviderEnum::OD,
        );

        $manufacturers = $this->manufacturersWithExisting($manufacturer);
        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('findMinMsId')->once()->andReturnNull();
        $vehicles->shouldReceive('findByMsId')->once()->with(200)->andReturnNull();

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (VehicleData $data): bool => $data->msId === 200
                && $data->provider === ProviderEnum::OD
                && $data->manufacturerId === 3))
            ->andReturn($expected);

        $service = $this->service(
            command: $command,
            vehicles: $vehicles,
            manufacturers: $manufacturers,
        );

        $this->assertSame($expected, $service->upsertFromRow($this->validRow()));

        Event::assertDispatched(VehicleCreated::class);
    }

    public function test_manual_sheet_import_keeps_existing_provider_and_locked_fields(): void
    {
        Event::fake([VehicleUpdated::class]);

        $incomingManufacturer = new ManufacturerData(
            mfaId: 20,
            name: 'Incoming',
            provider: ProviderEnum::OD,
            id: 2,
        );
        $existing = new VehicleData(
            msId: 200,
            mfaId: 10,
            manufacturerId: 1,
            name: 'TD old name',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::SALOON,
            provider: ProviderEnum::TD,
            generation: 'TD generation',
            generationYearFrom: 2010,
            generationYearTo: 2012,
            parentId: 77,
            id: 5,
        );
        $updated = new VehicleData(
            msId: 200,
            mfaId: 10,
            manufacturerId: 1,
            name: 'Octavia',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::SALOON,
            provider: ProviderEnum::TD,
            generation: 'TD generation',
            generationYearFrom: 2013,
            generationYearTo: 2020,
            parentId: 77,
            id: 5,
        );

        $manufacturers = $this->manufacturersWithExisting($incomingManufacturer);
        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('findMinMsId')->once()->andReturnNull();
        $vehicles->shouldReceive('findByMsId')->once()->with(200)->andReturn($existing);

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldReceive('updateByMsId')
            ->once()
            ->with(Mockery::on(fn (VehicleData $data): bool => $data->msId === 200
                && $data->mfaId === 10
                && $data->manufacturerId === 1
                && $data->name === 'Octavia'
                && $data->typeCarcase === CarcaseTypeEnum::SALOON
                && $data->provider === ProviderEnum::TD
                && $data->generation === 'TD generation'
                && $data->parentId === 77))
            ->andReturn($updated);

        $service = $this->service(
            command: $command,
            vehicles: $vehicles,
            manufacturers: $manufacturers,
        );

        $this->assertSame($updated, $service->upsertFromRow($this->validRow(
            mfaId: 20,
            provider: ProviderEnum::OD,
        )));

        Event::assertDispatched(VehicleUpdated::class);
    }

    private function service(
        VehicleCommandInterface $command,
        VehicleRepositoryInterface $vehicles,
        ManufacturerRepositoryInterface $manufacturers,
    ): UpsertVehicleFromSheetService {
        $manufacturerCommand = Mockery::mock(ManufacturerCommandInterface::class);
        $manufacturerCommand->shouldNotReceive('create');

        return new UpsertVehicleFromSheetService(
            command: $command,
            factory: new VehicleDataFactory,
            manufacturerFactory: new ManufacturerDataFactory,
            vehicles: $vehicles,
            manufacturers: $manufacturers,
            manufacturerCommand: $manufacturerCommand,
            writePolicy: new VehicleImportWritePolicy(new NullLogger),
        );
    }

    private function manufacturersWithExisting(ManufacturerData $manufacturer): ManufacturerRepositoryInterface
    {
        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('findMinMfaId')->once()->andReturnNull();
        $manufacturers->shouldReceive('findByMfaId')->once()->with($manufacturer->mfaId)->andReturn($manufacturer);

        return $manufacturers;
    }

    private function validRow(
        int $mfaId = 10,
        ProviderEnum $provider = ProviderEnum::OD,
    ): VehicleSheetRowDTO {
        return new VehicleSheetRowDTO(
            excelTableId: null,
            mfaId: $mfaId,
            msId: 200,
            manufacturerName: 'Skoda',
            name: 'Octavia',
            localizedName: null,
            generationShort: null,
            generation: 'Incoming generation',
            generationYearFrom: 2013,
            generationYearTo: 2020,
            typeCarcase: CarcaseTypeEnum::HATCHBACK->value,
            type: VehicleTypeEnum::PC->value,
            provider: $provider->value,
            parentMsId: null,
            steeringType: SteeringTypeEnum::LEFT->value,
            isAllow: false,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
