<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers\EngineMutationRequestedHandler;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers\ManufacturerMutationRequestedHandler;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers\ModificationMutationRequestedHandler;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Shared\Results\DTO\CatalogMutationCompleted as WireCatalogMutationCompleted;
use Tests\TestCase;

final class CatalogMutationRequestedHandlerTest extends TestCase
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

    public function test_catalog_mutation_result_matches_published_wire_contract(): void
    {
        $result = new CatalogMutationResultDTO(
            userId: 42,
            operationId: 'vehicles-result-wire-contract',
            entity: CatalogEntityEnum::Vehicle,
            operation: CatalogMutationOperationEnum::Create,
            status: CatalogMutationStatusEnum::Completed,
            externalId: 501,
            recordId: 1001,
            errors: [],
        );

        $wirePayload = WireCatalogMutationCompleted::fromArray($result->toArray())->toArray();

        self::assertSame([
            'user_id' => 42,
            'operation_id' => 'vehicles-result-wire-contract',
            'entity' => 'vehicle',
            'operation' => 'create',
            'status' => 'completed',
            'external_id' => 501,
            'record_id' => 1001,
            'errors' => [],
        ], $wirePayload);
    }

    public function test_manufacturer_create_update_and_delete_messages(): void
    {
        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(3)
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Manufacturer
                && $result->status === CatalogMutationStatusEnum::Completed));

        app(ManufacturerMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'manufacturer-create-1',
            'operation' => 'create',
            'manufacturer' => [
                'mfa_id' => 100,
                'name' => 'Skoda',
                'provider' => ProviderEnum::OD->value,
            ],
        ]);

        app(ManufacturerMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'manufacturer-update-1',
            'operation' => 'update',
            'manufacturer' => [
                'mfa_id' => 100,
                'name' => 'Skoda Auto',
                'provider' => ProviderEnum::TD->value,
            ],
        ]);

        app(ManufacturerMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'manufacturer-delete-1',
            'operation' => 'delete',
            'manufacturer' => [
                'mfa_id' => 100,
            ],
        ]);

        $this->assertDatabaseMissing('manufacturers', ['mfa_id' => 100]);
    }

    public function test_manufacturer_delete_cascades_related_vehicles(): void
    {
        $manufacturer = $this->createManufacturer(101);
        $vehicle = $this->createVehicle(601, $manufacturer);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Manufacturer
                && $result->operation === CatalogMutationOperationEnum::Delete
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->recordId === $manufacturer->id));

        app(ManufacturerMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'manufacturer-delete-cascade-1',
            'operation' => 'delete',
            'manufacturer' => [
                'mfa_id' => 101,
            ],
        ]);

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('manufacturers', ['mfa_id' => 101]);
    }

    public function test_engine_create_update_and_delete_messages(): void
    {
        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(3)
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Engine
                && $result->status === CatalogMutationStatusEnum::Completed));

        app(EngineMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-create-1',
            'operation' => 'create',
            'engine' => [
                'eng_id' => 200,
                'code_engine' => 'ABC',
                'engine_capacity' => '1.8',
            ],
        ]);

        app(EngineMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-update-1',
            'operation' => 'update',
            'engine' => [
                'eng_id' => 200,
                'code_engine' => 'ABC2',
                'engine_capacity' => '2.0',
            ],
        ]);

        app(EngineMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-delete-1',
            'operation' => 'delete',
            'engine' => [
                'eng_id' => 200,
            ],
        ]);

        $this->assertDatabaseMissing('engines', ['eng_id' => 200]);
    }

    public function test_engine_delete_cascades_engine_modifications(): void
    {
        $manufacturer = $this->createManufacturer(102);
        $vehicle = $this->createVehicle(602, $manufacturer);
        $engine = Engine::query()->create(['eng_id' => 201, 'code_engine' => 'ABC']);
        $modificationId = DB::table('modifications')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $vehicle->ms_id,
            'mod_id' => 3001,
            'type' => VehicleTypeEnum::PC->value,
        ]);
        DB::table('engine_modification')->insert([
            'engine_id' => $engine->id,
            'modification_id' => $modificationId,
            'eng_id' => $engine->eng_id,
            'mod_id' => 3001,
            'type' => VehicleTypeEnum::PC->value,
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Engine
                && $result->operation === CatalogMutationOperationEnum::Delete
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->recordId === $engine->id));

        app(EngineMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-delete-cascade-1',
            'operation' => 'delete',
            'engine' => [
                'eng_id' => 201,
            ],
        ]);

        $this->assertDatabaseMissing('engine_modification', ['engine_id' => $engine->id]);
        $this->assertDatabaseMissing('engines', ['eng_id' => 201]);
        $this->assertDatabaseHas('modifications', ['id' => $modificationId]);
    }

    public function test_modification_create_update_and_delete_messages(): void
    {
        $manufacturer = $this->createManufacturer(103);
        $vehicle = $this->createVehicle(603, $manufacturer);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(3)
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Modification
                && $result->status === CatalogMutationStatusEnum::Completed));

        app(ModificationMutationRequestedHandler::class)->handle($this->modificationPayload(
            operationId: 'modification-create-1',
            operation: 'create',
            msId: $vehicle->ms_id,
            modId: 4001,
            description: 'Old',
        ));

        app(ModificationMutationRequestedHandler::class)->handle($this->modificationPayload(
            operationId: 'modification-update-1',
            operation: 'update',
            msId: $vehicle->ms_id,
            modId: 4001,
            description: 'New',
        ));

        app(ModificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'modification-delete-1',
            'operation' => 'delete',
            'modification' => [
                'mod_id' => 4001,
                'type' => VehicleTypeEnum::PC->value,
            ],
        ]);

        $this->assertDatabaseMissing('modifications', ['mod_id' => 4001]);
    }

    public function test_modification_delete_cascades_engine_modifications(): void
    {
        $manufacturer = $this->createManufacturer(104);
        $vehicle = $this->createVehicle(604, $manufacturer);
        $engine = Engine::query()->create(['eng_id' => 202, 'code_engine' => 'DEF']);
        $modification = Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $vehicle->ms_id,
            'mod_id' => 4002,
            'type' => VehicleTypeEnum::PC->value,
        ]);
        DB::table('engine_modification')->insert([
            'engine_id' => $engine->id,
            'modification_id' => $modification->id,
            'eng_id' => $engine->eng_id,
            'mod_id' => $modification->mod_id,
            'type' => VehicleTypeEnum::PC->value,
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Modification
                && $result->operation === CatalogMutationOperationEnum::Delete
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->recordId === $modification->id));

        app(ModificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'modification-delete-cascade-1',
            'operation' => 'delete',
            'modification' => [
                'mod_id' => 4002,
                'type' => VehicleTypeEnum::PC->value,
            ],
        ]);

        $this->assertDatabaseMissing('engine_modification', ['modification_id' => $modification->id]);
        $this->assertDatabaseMissing('modifications', ['mod_id' => 4002]);
        $this->assertDatabaseHas('engines', ['id' => $engine->id]);
    }

    public function test_modification_create_rejects_unknown_nested_engine_without_creating_it(): void
    {
        $manufacturer = $this->createManufacturer(105);
        $vehicle = $this->createVehicle(605, $manufacturer);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Modification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::NotFound->value));

        app(ModificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'modification-create-unknown-engine-1',
            'operation' => 'create',
            'modification' => [
                'mod_id' => 4003,
                'ms_id' => $vehicle->ms_id,
                'type' => VehicleTypeEnum::PC->value,
                'description' => '1.4 TSI',
                'engines' => [
                    [
                        'eng_id' => 999999,
                        'code_engine' => 'NEW',
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseMissing('engines', ['eng_id' => 999999]);
        $this->assertDatabaseMissing('modifications', ['mod_id' => 4003]);
    }

    public function test_catalog_mutation_events_have_unique_names_and_handlers(): void
    {
        $this->assertSame([ManufacturerMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.MANUFACTURER_CREATE_REQUESTED'));
        $this->assertSame([ManufacturerMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.MANUFACTURER_UPDATE_REQUESTED'));
        $this->assertSame([ManufacturerMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.MANUFACTURER_DELETE_REQUESTED'));
        $this->assertSame([EngineMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.ENGINE_CREATE_REQUESTED'));
        $this->assertSame([EngineMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.ENGINE_UPDATE_REQUESTED'));
        $this->assertSame([EngineMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.ENGINE_DELETE_REQUESTED'));
        $this->assertSame([ModificationMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.MODIFICATION_CREATE_REQUESTED'));
        $this->assertSame([ModificationMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.MODIFICATION_UPDATE_REQUESTED'));
        $this->assertSame([ModificationMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.MODIFICATION_DELETE_REQUESTED'));
        $this->assertSame('vehicles.catalog.mutation.completed', config('rabbit-transport.outbound.VEHICLES_CATALOG_MUTATION_COMPLETED'));
    }

    private function createManufacturer(int $mfaId): Manufacturer
    {
        return Manufacturer::query()->create([
            'mfa_id' => $mfaId,
            'name' => 'Skoda',
            'provider' => ProviderEnum::TD->value,
        ]);
    }

    private function createVehicle(int $msId, Manufacturer $manufacturer): Vehicle
    {
        return Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => $manufacturer->mfa_id,
            'ms_id' => $msId,
            'name' => 'Octavia',
            'generation' => 'III',
            'generation_year_from' => 2013,
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
    private function modificationPayload(
        string $operationId,
        string $operation,
        int $msId,
        int $modId,
        string $description,
    ): array {
        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'modification' => [
                'mod_id' => $modId,
                'ms_id' => $msId,
                'type' => VehicleTypeEnum::PC->value,
                'description' => $description,
            ],
        ];
    }
}
