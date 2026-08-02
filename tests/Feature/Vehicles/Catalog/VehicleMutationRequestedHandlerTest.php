<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers\VehicleMutationRequestedHandler;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
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
    ): Vehicle {
        return Vehicle::query()->create([
            'parent_id' => $parentId,
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => $manufacturer->mfa_id,
            'ms_id' => $msId,
            'name' => $name,
            'localized_name' => null,
            'excel_table_id' => null,
            'generation' => null,
            'generation_short' => null,
            'generation_year_from' => 2013,
            'generation_year_to' => 2020,
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => CarcaseTypeEnum::HATCHBACK->value,
            'provider' => ProviderEnum::OD->value,
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
    ): array {
        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'vehicle' => [
                'ms_id' => $msId,
                'mfa_id' => $mfaId,
                'name' => $name,
                'type' => VehicleTypeEnum::PC->value,
                'type_carcase' => CarcaseTypeEnum::HATCHBACK->value,
                'provider' => ProviderEnum::OD->value,
                'steering_type' => SteeringTypeEnum::LEFT->value,
                'generation_year_from' => 2013,
                'generation_year_to' => 2020,
                'is_allow' => $isAllow,
            ],
        ];
    }
}
