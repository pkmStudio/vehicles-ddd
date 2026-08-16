<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers\VehicleMutationRequestedHandler;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Mutation\DTO\VehicleMutationPayload as WireVehicleMutationPayload;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Mutation\DTO\VehicleMutationRequested as WireVehicleMutationRequested;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\CatalogMutationOperation as WireCatalogMutationOperation;
use Tests\TestCase;

/**
 * Покрывает RabbitMQ write-сценарий Vehicles/Catalog: входящее сообщение брокера
 * валидируется handler-ом, превращается в DTO, проходит через use case и пишет каталог.
 */
final class VehicleMutationRequestedHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_create_vehicle_message_creates_vehicle(): void
    {
        $manufacturer = $this->createManufacturer();

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function (CatalogMutationResultDTO $result): bool {
                return $result->entity === CatalogEntityEnum::Vehicle
                    && $result->operation === CatalogMutationOperationEnum::Create
                    && $result->status === CatalogMutationStatusEnum::Completed
                    && $result->operationId === 'vehicle-create-1'
                    && $result->externalId === 501
                    && $result->recordId !== null;
            }));

        app(VehicleMutationRequestedHandler::class)->handle($this->vehiclePayload(
            operationId: 'vehicle-create-1',
            operation: 'create',
            msId: 501,
            mfaId: $manufacturer->mfa_id,
            name: 'Octavia',
        ));

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 501,
            'mfa_id' => $manufacturer->mfa_id,
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Octavia',
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => CarcaseTypeEnum::HATCHBACK->value,
        ]);
    }

    public function test_create_vehicle_message_accepts_published_wire_contract_payload(): void
    {
        $manufacturer = $this->createManufacturer();

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function (CatalogMutationResultDTO $result): bool {
                return $result->entity === CatalogEntityEnum::Vehicle
                    && $result->operation === CatalogMutationOperationEnum::Create
                    && $result->status === CatalogMutationStatusEnum::Completed
                    && $result->operationId === 'vehicle-create-wire-contract'
                    && $result->externalId === 508
                    && $result->recordId !== null;
            }));

        $message = new WireVehicleMutationRequested(
            userId: 42,
            operationId: 'vehicle-create-wire-contract',
            operation: WireCatalogMutationOperation::Create->value,
            vehicle: new WireVehicleMutationPayload(
                msId: 508,
                mfaId: $manufacturer->mfa_id,
                name: 'Wire Karoq',
                type: VehicleTypeEnum::PC->value,
                typeCarcase: CarcaseTypeEnum::HATCHBACK->value,
                provider: ProviderEnum::OD->value,
                steeringType: SteeringTypeEnum::LEFT->value,
                isAllow: true,
            ),
        );

        app(VehicleMutationRequestedHandler::class)->handle($message->toArray());

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 508,
            'mfa_id' => $manufacturer->mfa_id,
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Wire Karoq',
            'is_allow' => true,
        ]);
    }

    public function test_create_vehicle_message_forces_od_provider(): void
    {
        $manufacturer = $this->createManufacturer();

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Vehicle
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->operationId === 'vehicle-create-provider-forced'));

        app(VehicleMutationRequestedHandler::class)->handle($this->vehiclePayload(
            operationId: 'vehicle-create-provider-forced',
            operation: 'create',
            msId: 507,
            mfaId: $manufacturer->mfa_id,
            name: 'Provider forced',
            provider: ProviderEnum::TD,
        ));

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 507,
            'provider' => ProviderEnum::OD->value,
        ]);
    }

    public function test_create_vehicle_message_generates_ms_id_when_missing(): void
    {
        $manufacturer = $this->createManufacturer();
        $this->createVehicle(msId: 701, manufacturer: $manufacturer, name: 'Existing');

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function (CatalogMutationResultDTO $result): bool {
                return $result->entity === CatalogEntityEnum::Vehicle
                    && $result->operation === CatalogMutationOperationEnum::Create
                    && $result->status === CatalogMutationStatusEnum::Completed
                    && $result->operationId === 'vehicle-create-auto-ms-id'
                    && $result->externalId === -1
                    && $result->recordId !== null;
            }));

        app(VehicleMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'vehicle-create-auto-ms-id',
            'operation' => 'create',
            'vehicle' => [
                'mfa_id' => $manufacturer->mfa_id,
                'name' => 'Generated MS ID',
                'type' => VehicleTypeEnum::PC->value,
                'type_carcase' => CarcaseTypeEnum::HATCHBACK->value,
                'provider' => ProviderEnum::OD->value,
                'steering_type' => SteeringTypeEnum::LEFT->value,
            ],
        ]);

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => -1,
            'mfa_id' => $manufacturer->mfa_id,
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Generated MS ID',
        ]);
    }

    public function test_update_vehicle_message_updates_vehicle(): void
    {
        $manufacturer = $this->createManufacturer();
        $this->createVehicle(msId: 502, manufacturer: $manufacturer, name: 'Old name');

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function (CatalogMutationResultDTO $result): bool {
                return $result->entity === CatalogEntityEnum::Vehicle
                    && $result->operation === CatalogMutationOperationEnum::Update
                    && $result->status === CatalogMutationStatusEnum::Completed
                    && $result->operationId === 'vehicle-update-1'
                    && $result->externalId === 502
                    && $result->recordId !== null;
            }));

        app(VehicleMutationRequestedHandler::class)->handle($this->vehiclePayload(
            operationId: 'vehicle-update-1',
            operation: 'update',
            msId: 502,
            mfaId: $manufacturer->mfa_id,
            name: 'New name',
            isAllow: true,
        ));

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 502,
            'name' => 'New name',
            'is_allow' => true,
        ]);
    }

    public function test_accepts_dan_center_vehicle_rest_update_sample_payload(): void
    {
        $manufacturer = $this->createManufacturer(900);
        $this->createVehicle(msId: 7001, manufacturer: $manufacturer, name: 'Original Vehicle');

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Vehicle
                && $result->operation === CatalogMutationOperationEnum::Update
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->operationId === 'dan-center-vehicle-rest-update-sample'
                && $result->externalId === 7001));

        app(VehicleMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'dan-center-vehicle-rest-update-sample',
            'operation' => 'update',
            'vehicle' => [
                'ms_id' => 7001,
                'mfa_id' => 900,
                'name' => 'Updated Vehicle',
                'type' => 'PC',
                'type_carcase' => 'HATCHBACK',
                'provider' => 'OD',
                'steering_type' => 'LEFT',
                'is_allow' => true,
            ],
        ]);

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 7001,
            'mfa_id' => 900,
            'name' => 'Updated Vehicle',
            'is_allow' => true,
        ]);
    }

    public function test_update_od_vehicle_allows_catalog_managed_fields(): void
    {
        $existingManufacturer = $this->createManufacturer(21);
        $incomingManufacturer = $this->createManufacturer(22);
        $incomingParent = $this->createVehicle(
            msId: 620,
            manufacturer: $incomingManufacturer,
            provider: ProviderEnum::OD,
        );
        $this->createVehicle(
            msId: 621,
            manufacturer: $existingManufacturer,
            name: 'OD old name',
            provider: ProviderEnum::OD,
            generation: 'Old generation',
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
        );

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Vehicle
                && $result->operation === CatalogMutationOperationEnum::Update
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->operationId === 'vehicle-update-od-managed'));

        app(VehicleMutationRequestedHandler::class)->handle($this->vehiclePayload(
            operationId: 'vehicle-update-od-managed',
            operation: 'update',
            msId: 621,
            mfaId: $incomingManufacturer->mfa_id,
            name: 'OD new name',
            provider: ProviderEnum::TD,
            parentMsId: $incomingParent->ms_id,
            generation: 'New generation',
            typeCarcase: CarcaseTypeEnum::SALOON,
        ));

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 621,
            'manufacturer_id' => $incomingManufacturer->id,
            'mfa_id' => $incomingManufacturer->mfa_id,
            'parent_id' => $incomingParent->id,
            'name' => 'OD new name',
            'generation' => 'New generation',
            'type_carcase' => CarcaseTypeEnum::SALOON->value,
            'provider' => ProviderEnum::OD->value,
        ]);
    }

    public function test_update_td_vehicle_keeps_locked_fields_and_updates_common_fields(): void
    {
        $existingManufacturer = $this->createManufacturer(11);
        $incomingManufacturer = $this->createManufacturer(12);
        $existingParent = $this->createVehicle(
            msId: 610,
            manufacturer: $existingManufacturer,
            provider: ProviderEnum::TD,
        );
        $incomingParent = $this->createVehicle(
            msId: 611,
            manufacturer: $incomingManufacturer,
            provider: ProviderEnum::OD,
        );
        $this->createVehicle(
            msId: 612,
            manufacturer: $existingManufacturer,
            parentId: $existingParent->id,
            name: 'TD old name',
            provider: ProviderEnum::TD,
            generation: 'TD generation',
            typeCarcase: CarcaseTypeEnum::SALOON,
        );

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Vehicle
                && $result->operation === CatalogMutationOperationEnum::Update
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->operationId === 'vehicle-update-td-locked'));

        app(VehicleMutationRequestedHandler::class)->handle($this->vehiclePayload(
            operationId: 'vehicle-update-td-locked',
            operation: 'update',
            msId: 612,
            mfaId: $incomingManufacturer->mfa_id,
            name: 'TD new name',
            isAllow: true,
            provider: ProviderEnum::OD,
            parentMsId: $incomingParent->ms_id,
            generation: 'Incoming generation',
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
        ));

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 612,
            'manufacturer_id' => $existingManufacturer->id,
            'mfa_id' => $existingManufacturer->mfa_id,
            'parent_id' => $existingParent->id,
            'name' => 'TD new name',
            'generation' => 'TD generation',
            'type_carcase' => CarcaseTypeEnum::SALOON->value,
            'provider' => ProviderEnum::TD->value,
            'is_allow' => true,
        ]);
    }

    public function test_delete_vehicle_message_deletes_leaf_vehicle(): void
    {
        $manufacturer = $this->createManufacturer();
        $vehicle = $this->createVehicle(msId: 503, manufacturer: $manufacturer);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function (CatalogMutationResultDTO $result) use ($vehicle): bool {
                return $result->entity === CatalogEntityEnum::Vehicle
                    && $result->operation === CatalogMutationOperationEnum::Delete
                    && $result->status === CatalogMutationStatusEnum::Completed
                    && $result->operationId === 'vehicle-delete-1'
                    && $result->externalId === 503
                    && $result->recordId === $vehicle->id;
            }));

        app(VehicleMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'vehicle-delete-1',
            'operation' => 'delete',
            'vehicle' => [
                'ms_id' => 503,
            ],
        ]);

        $this->assertDatabaseMissing('vehicles', [
            'ms_id' => 503,
        ]);
    }

    public function test_delete_td_vehicle_message_is_rejected(): void
    {
        $manufacturer = $this->createManufacturer();
        $vehicle = $this->createVehicle(
            msId: 507,
            manufacturer: $manufacturer,
            provider: ProviderEnum::TD,
        );

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function (CatalogMutationResultDTO $result) use ($vehicle): bool {
                return $result->entity === CatalogEntityEnum::Vehicle
                    && $result->operation === CatalogMutationOperationEnum::Delete
                    && $result->status === CatalogMutationStatusEnum::Rejected
                    && $result->reason === CatalogMutationRejectReasonEnum::ProviderDeleteForbidden->value
                    && $result->operationId === 'vehicle-delete-td-1'
                    && $result->externalId === 507
                    && $result->recordId === $vehicle->id;
            }));

        app(VehicleMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'vehicle-delete-td-1',
            'operation' => 'delete',
            'vehicle' => [
                'ms_id' => 507,
            ],
        ]);

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 507,
            'provider' => ProviderEnum::TD->value,
        ]);
    }

    public function test_delete_vehicle_message_cascades_related_records(): void
    {
        $manufacturer = $this->createManufacturer();
        $vehicle = $this->createVehicle(msId: 504, manufacturer: $manufacturer);
        $child = $this->createVehicle(msId: 505, manufacturer: $manufacturer, parentId: $vehicle->id);

        $modificationId = DB::table('modifications')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $vehicle->ms_id,
            'mod_id' => 7001,
            'type' => VehicleTypeEnum::PC->value,
            'year_from' => 2013,
            'description' => '1.4 TSI',
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => json_encode(['year_from', 'year_to']),
        ]);

        DB::table('part_specifications')->insert([
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => $vehicle->id,
            'template' => 'wiper',
            'details' => DB::connection()->getDriverName() === 'pgsql'
                ? DB::raw("'[]'::jsonb")
                : json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(function (CatalogMutationResultDTO $result) use ($vehicle): bool {
                return $result->entity === CatalogEntityEnum::Vehicle
                    && $result->operation === CatalogMutationOperationEnum::Delete
                    && $result->status === CatalogMutationStatusEnum::Completed
                    && $result->recordId === $vehicle->id;
            }));

        app(VehicleMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'vehicle-delete-cascade-1',
            'operation' => 'delete',
            'vehicle' => [
                'ms_id' => 504,
            ],
        ]);

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('vehicles', ['id' => $child->id]);
        $this->assertDatabaseMissing('modifications', ['id' => $modificationId]);
        $this->assertDatabaseMissing('part_specifications', [
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => $vehicle->id,
        ]);
    }

    public function test_duplicate_operation_id_is_skipped(): void
    {
        $manufacturer = $this->createManufacturer();

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')->once();

        $payload = $this->vehiclePayload(
            operationId: 'vehicle-create-dup',
            operation: 'create',
            msId: 506,
            mfaId: $manufacturer->mfa_id,
            name: 'Duplicate guarded',
        );

        app(VehicleMutationRequestedHandler::class)->handle($payload);
        app(VehicleMutationRequestedHandler::class)->handle($payload);

        $this->assertSame(1, Vehicle::query()->where('ms_id', 506)->count());
    }

    public function test_vehicle_mutation_events_have_unique_names_and_same_handler(): void
    {
        $handler = [VehicleMutationRequestedHandler::class, 'handle'];

        $this->assertSame($handler, config('rabbit-transport.inbound.VEHICLE_CREATE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.VEHICLE_UPDATE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.VEHICLE_DELETE_REQUESTED'));

        $bindings = (array) config('rabbit-transport.setup.bindings');

        $this->assertContains('crm.vehicles.create', $bindings);
        $this->assertContains('crm.vehicles.update', $bindings);
        $this->assertContains('crm.vehicles.delete', $bindings);
    }

    private function createManufacturer(int $mfaId = 10): Manufacturer
    {
        return Manufacturer::query()->create([
            'mfa_id' => $mfaId,
            'name' => 'Skoda',
            'provider' => ProviderEnum::TD->value,
        ]);
    }

    private function createVehicle(
        int $msId,
        Manufacturer $manufacturer,
        ?int $parentId = null,
        string $name = 'Octavia',
        ProviderEnum $provider = ProviderEnum::OD,
        string $generation = 'III',
        CarcaseTypeEnum $typeCarcase = CarcaseTypeEnum::HATCHBACK,
    ): Vehicle {
        return Vehicle::query()->create([
            'parent_id' => $parentId,
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => $manufacturer->mfa_id,
            'ms_id' => $msId,
            'name' => $name,
            'localized_name' => null,
            'excel_table_id' => null,
            'generation' => $generation,
            'generation_short' => null,
            'generation_year_from' => 2013,
            'generation_year_to' => 2020,
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => $typeCarcase->value,
            'provider' => $provider->value,
            'steering_type' => SteeringTypeEnum::LEFT->value,
            'is_allow' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function vehiclePayload(
        string $operationId,
        string $operation,
        int $msId,
        int $mfaId,
        string $name,
        bool $isAllow = false,
        ProviderEnum $provider = ProviderEnum::OD,
        ?int $parentMsId = null,
        ?string $generation = null,
        CarcaseTypeEnum $typeCarcase = CarcaseTypeEnum::HATCHBACK,
    ): array {
        $vehicle = [
            'ms_id' => $msId,
            'mfa_id' => $mfaId,
            'name' => $name,
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => $typeCarcase->value,
            'provider' => $provider->value,
            'steering_type' => SteeringTypeEnum::LEFT->value,
            'generation' => $generation,
            'generation_year_from' => 2013,
            'generation_year_to' => 2020,
            'is_allow' => $isAllow,
        ];

        if ($parentMsId !== null) {
            $vehicle['parent_ms_id'] = $parentMsId;
        }

        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'vehicle' => $vehicle,
        ];
    }
}
