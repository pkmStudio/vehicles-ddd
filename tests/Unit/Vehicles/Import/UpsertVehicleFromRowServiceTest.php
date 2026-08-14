<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Application\Factories\ManufacturerDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Factories\VehicleDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Vehicle\UpsertVehicleFromRowService;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\ProviderOwnershipPolicy;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\VehicleWritePolicy;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

final class UpsertVehicleFromRowServiceTest extends TestCase
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
            generation: 'Incoming generation',
            generationYearFrom: 2013,
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

    public function test_manual_sheet_import_rejects_existing_vehicle_from_another_provider(): void
    {
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
        $manufacturers = $this->manufacturersWithExisting($incomingManufacturer);
        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('findMinMsId')->once()->andReturnNull();
        $vehicles->shouldReceive('findByMsId')->once()->with(200)->andReturn($existing);

        $command = Mockery::mock(VehicleCommandInterface::class);
        $command->shouldNotReceive('update');

        $service = $this->service(
            command: $command,
            vehicles: $vehicles,
            manufacturers: $manufacturers,
        );

        $this->expectException(ImportRowValidationException::class);
        $this->expectExceptionMessage('уже принадлежит provider=TD');

        $service->upsertFromRow($this->validRow(
            mfaId: 20,
            provider: ProviderEnum::OD,
        ));
    }

    private function service(
        VehicleCommandInterface $command,
        VehicleRepositoryInterface $vehicles,
        ManufacturerRepositoryInterface $manufacturers,
    ): UpsertVehicleFromRowService {
        $manufacturerCommand = Mockery::mock(ManufacturerCommandInterface::class);
        $manufacturerCommand->shouldNotReceive('create');

        return new UpsertVehicleFromRowService(
            command: $command,
            factory: new VehicleDataFactory,
            manufacturerFactory: new ManufacturerDataFactory,
            vehicles: $vehicles,
            manufacturers: $manufacturers,
            manufacturerCommand: $manufacturerCommand,
            writePolicy: new VehicleWritePolicy(new ProviderOwnershipPolicy),
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
