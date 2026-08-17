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
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
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
                'code_engine' => 'ABC',
                'power_kw_start' => 100,
                'power_ps_start' => 136,
                'fuel_type' => EngineFuelTypeEnum::PETROL->value,
                'engine_capacity' => 1.8,
                'provider' => ProviderEnum::OD->value,
                'allow_change_fields' => [],
            ],
        ]);

        $engine = Engine::query()->where('code_engine', 'ABC')->firstOrFail();
        $this->assertLessThan(0, $engine->eng_id);

        app(EngineMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-update-1',
            'operation' => 'update',
            'engine' => [
                'eng_id' => $engine->eng_id,
                'code_engine' => 'ABC2',
                'power_kw_start' => 110,
                'power_ps_start' => 150,
                'fuel_type' => EngineFuelTypeEnum::PETROL->value,
                'engine_capacity' => 2.0,
                'provider' => ProviderEnum::OD->value,
                'allow_change_fields' => [],
            ],
        ]);

        app(EngineMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-delete-1',
            'operation' => 'delete',
            'engine' => [
                'eng_id' => $engine->eng_id,
            ],
        ]);

        $this->assertDatabaseMissing('engines', ['eng_id' => $engine->eng_id]);
    }

    public function test_engine_create_rejects_negative_numeric_values(): void
    {
        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Engine
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Failed
                && $result->reason === CatalogMutationRejectReasonEnum::ContractMismatch->value
                && in_array('engine.cylinder_count', $result->errors['invalid_keys'] ?? [], true)));

        app(EngineMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-create-negative-cylinder-count',
            'operation' => 'create',
            'engine' => [
                'code_engine' => 'NEGATIVE',
                'power_kw_start' => 100,
                'power_ps_start' => 136,
                'fuel_type' => EngineFuelTypeEnum::PETROL->value,
                'engine_capacity' => 1.8,
                'cylinder_count' => -4,
                'provider' => ProviderEnum::OD->value,
                'allow_change_fields' => [],
            ],
        ]);

        $this->assertDatabaseMissing('engines', ['code_engine' => 'NEGATIVE']);
    }

    public function test_engine_delete_cascades_engine_modifications(): void
    {
        $manufacturer = $this->createManufacturer(102);
        $vehicle = $this->createVehicle(602, $manufacturer);
        $engine = Engine::query()->create([
            'eng_id' => 201,
            'code_engine' => 'ABC',
            'power_kw_start' => 100,
            'power_ps_start' => 136,
            'fuel_type' => EngineFuelTypeEnum::PETROL->value,
            'provider' => ProviderEnum::OD->value,
            'allow_change_fields' => [],
        ]);
        $modificationId = DB::table('modifications')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $vehicle->ms_id,
            'mod_id' => 3001,
            'type' => VehicleTypeEnum::PC->value,
            'year_from' => 2013,
            'description' => '1.4 TSI',
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => json_encode(['year_from', 'year_to']),
        ]);
        DB::table('engine_modification')->insert([
            'engine_id' => $engine->id,
            'modification_id' => $modificationId,
            'eng_id' => $engine->eng_id,
            'mod_id' => 3001,
            'type' => VehicleTypeEnum::PC->value,
            'provider' => ProviderEnum::TD->value,
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

    public function test_td_engine_delete_is_rejected(): void
    {
        $engine = Engine::query()->create([
            'eng_id' => 203,
            'code_engine' => 'TDLOCK',
            'power_kw_start' => 100,
            'power_ps_start' => 136,
            'fuel_type' => EngineFuelTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Engine
                && $result->operation === CatalogMutationOperationEnum::Delete
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::ProviderDeleteForbidden->value
                && $result->recordId === $engine->id));

        app(EngineMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-delete-td-1',
            'operation' => 'delete',
            'engine' => [
                'eng_id' => 203,
            ],
        ]);

        $this->assertDatabaseHas('engines', [
            'eng_id' => 203,
            'provider' => ProviderEnum::TD->value,
        ]);
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
        $engine = Engine::query()->create([
            'eng_id' => 202,
            'code_engine' => 'DEF',
            'power_kw_start' => 100,
            'power_ps_start' => 136,
            'fuel_type' => EngineFuelTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);
        $modification = Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $vehicle->ms_id,
            'mod_id' => 4002,
            'type' => VehicleTypeEnum::PC->value,
            'year_from' => 2013,
            'description' => '1.4 TSI',
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => ProviderEnum::OD->value,
            'allow_change_fields' => ['year_from', 'year_to'],
        ]);
        DB::table('engine_modification')->insert([
            'engine_id' => $engine->id,
            'modification_id' => $modification->id,
            'eng_id' => $engine->eng_id,
            'mod_id' => $modification->mod_id,
            'type' => VehicleTypeEnum::PC->value,
            'provider' => ProviderEnum::TD->value,
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

    public function test_td_modification_delete_is_rejected(): void
    {
        $manufacturer = $this->createManufacturer(106);
        $vehicle = $this->createVehicle(606, $manufacturer);
        $modification = Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $vehicle->ms_id,
            'mod_id' => 4004,
            'type' => VehicleTypeEnum::PC->value,
            'year_from' => 2013,
            'description' => '1.4 TSI',
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => ['year_from', 'year_to'],
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Modification
                && $result->operation === CatalogMutationOperationEnum::Delete
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::ProviderDeleteForbidden->value
                && $result->recordId === $modification->id));

        app(ModificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'modification-delete-td-1',
            'operation' => 'delete',
            'modification' => [
                'mod_id' => 4004,
                'type' => VehicleTypeEnum::PC->value,
            ],
        ]);

        $this->assertDatabaseHas('modifications', [
            'mod_id' => 4004,
            'provider' => ProviderEnum::TD->value,
        ]);
    }

    public function test_td_modification_update_can_add_od_engine_link_without_changing_td_link(): void
    {
        $manufacturer = $this->createManufacturer(107);
        $vehicle = $this->createVehicle(607, $manufacturer);
        $tdEngine = $this->createEngine(207, ProviderEnum::TD);
        $odEngine = $this->createEngine(208, ProviderEnum::OD);
        $modification = $this->createModification($vehicle, 4007, ProviderEnum::TD);

        DB::table('engine_modification')->insert([
            'engine_id' => $tdEngine->id,
            'modification_id' => $modification->id,
            'eng_id' => $tdEngine->eng_id,
            'mod_id' => $modification->mod_id,
            'type' => VehicleTypeEnum::PC->value,
            'provider' => ProviderEnum::TD->value,
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Modification
                && $result->operation === CatalogMutationOperationEnum::Update
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->recordId === $modification->id));

        app(ModificationMutationRequestedHandler::class)->handle($this->modificationUpdatePayload(
            operationId: 'modification-update-add-od-link-1',
            vehicle: $vehicle,
            modification: $modification,
            engines: [
                ['eng_id' => $tdEngine->eng_id, 'relation_provider' => ProviderEnum::TD->value],
                ['eng_id' => $odEngine->eng_id, 'relation_provider' => ProviderEnum::OD->value],
            ],
        ));

        $this->assertDatabaseHas('engine_modification', [
            'engine_id' => $tdEngine->id,
            'modification_id' => $modification->id,
            'provider' => ProviderEnum::TD->value,
        ]);
        $this->assertDatabaseHas('engine_modification', [
            'engine_id' => $odEngine->id,
            'modification_id' => $modification->id,
            'provider' => ProviderEnum::OD->value,
        ]);
    }

    public function test_modification_update_stores_new_engine_link_as_od_when_payload_claims_td(): void
    {
        $manufacturer = $this->createManufacturer(109);
        $vehicle = $this->createVehicle(609, $manufacturer);
        $tdEngine = $this->createEngine(211, ProviderEnum::TD);
        $odEngine = $this->createEngine(212, ProviderEnum::OD);
        $modification = $this->createModification($vehicle, 4009, ProviderEnum::TD);

        DB::table('engine_modification')->insert([
            'engine_id' => $tdEngine->id,
            'modification_id' => $modification->id,
            'eng_id' => $tdEngine->eng_id,
            'mod_id' => $modification->mod_id,
            'type' => VehicleTypeEnum::PC->value,
            'provider' => ProviderEnum::TD->value,
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Modification
                && $result->operation === CatalogMutationOperationEnum::Update
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->recordId === $modification->id));

        app(ModificationMutationRequestedHandler::class)->handle($this->modificationUpdatePayload(
            operationId: 'modification-update-forged-td-link-1',
            vehicle: $vehicle,
            modification: $modification,
            engines: [
                ['eng_id' => $tdEngine->eng_id, 'relation_provider' => ProviderEnum::TD->value],
                ['eng_id' => $odEngine->eng_id, 'relation_provider' => ProviderEnum::TD->value],
            ],
        ));

        $this->assertDatabaseHas('engine_modification', [
            'engine_id' => $tdEngine->id,
            'modification_id' => $modification->id,
            'provider' => ProviderEnum::TD->value,
        ]);
        $this->assertDatabaseHas('engine_modification', [
            'engine_id' => $odEngine->id,
            'modification_id' => $modification->id,
            'provider' => ProviderEnum::OD->value,
        ]);
    }

    public function test_td_modification_update_rejects_removing_td_engine_link(): void
    {
        $manufacturer = $this->createManufacturer(108);
        $vehicle = $this->createVehicle(608, $manufacturer);
        $tdEngine = $this->createEngine(209, ProviderEnum::TD);
        $odEngine = $this->createEngine(210, ProviderEnum::OD);
        $modification = $this->createModification($vehicle, 4008, ProviderEnum::TD);

        DB::table('engine_modification')->insert([
            'engine_id' => $tdEngine->id,
            'modification_id' => $modification->id,
            'eng_id' => $tdEngine->eng_id,
            'mod_id' => $modification->mod_id,
            'type' => VehicleTypeEnum::PC->value,
            'provider' => ProviderEnum::TD->value,
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Modification
                && $result->operation === CatalogMutationOperationEnum::Update
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::ProviderOwnershipConflict->value));

        app(ModificationMutationRequestedHandler::class)->handle($this->modificationUpdatePayload(
            operationId: 'modification-update-remove-td-link-1',
            vehicle: $vehicle,
            modification: $modification,
            engines: [
                ['eng_id' => $odEngine->eng_id, 'relation_provider' => ProviderEnum::OD->value],
            ],
        ));

        $this->assertDatabaseHas('engine_modification', [
            'engine_id' => $tdEngine->id,
            'modification_id' => $modification->id,
            'provider' => ProviderEnum::TD->value,
        ]);
        $this->assertDatabaseMissing('engine_modification', [
            'engine_id' => $odEngine->id,
            'modification_id' => $modification->id,
        ]);
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
                'year_from' => 2019,
                'description' => '1.4 TSI',
                'power_ps' => 150,
                'power_kw' => 110,
                'engine_type' => EngineTypeEnum::PETROL->value,
                'provider' => ProviderEnum::OD->value,
                'allow_change_fields' => [],
                'engines' => [
                    [
                        'eng_id' => 999999,
                        'code_engine' => 'NEW',
                        'relation_provider' => ProviderEnum::OD->value,
                        'allow_change_fields' => [],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseMissing('engines', ['eng_id' => 999999]);
        $this->assertDatabaseMissing('modifications', ['mod_id' => 4003]);
    }

    public function test_modification_create_stores_engine_link_as_od_when_payload_claims_td(): void
    {
        $manufacturer = $this->createManufacturer(110);
        $vehicle = $this->createVehicle(610, $manufacturer);
        $engine = $this->createEngine(213, ProviderEnum::TD);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::Modification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Completed));

        app(ModificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'modification-create-forged-td-link-1',
            'operation' => 'create',
            'modification' => [
                'mod_id' => 4010,
                'ms_id' => $vehicle->ms_id,
                'type' => VehicleTypeEnum::PC->value,
                'year_from' => 2019,
                'description' => '1.4 TSI',
                'power_ps' => 150,
                'power_kw' => 110,
                'engine_type' => EngineTypeEnum::PETROL->value,
                'provider' => ProviderEnum::OD->value,
                'allow_change_fields' => [],
                'engines' => [
                    ['eng_id' => $engine->eng_id, 'relation_provider' => ProviderEnum::TD->value],
                ],
            ],
        ]);

        $modification = Modification::query()
            ->where('mod_id', 4010)
            ->firstOrFail();

        $this->assertDatabaseHas('engine_modification', [
            'engine_id' => $engine->id,
            'modification_id' => $modification->id,
            'provider' => ProviderEnum::OD->value,
        ]);
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

    private function createEngine(int $engId, ProviderEnum $provider): Engine
    {
        return Engine::query()->create([
            'eng_id' => $engId,
            'code_engine' => 'ENG'.$engId,
            'power_kw_start' => 100,
            'power_ps_start' => 136,
            'fuel_type' => EngineFuelTypeEnum::PETROL->value,
            'provider' => $provider->value,
            'allow_change_fields' => [],
        ]);
    }

    private function createModification(Vehicle $vehicle, int $modId, ProviderEnum $provider): Modification
    {
        return Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => $vehicle->ms_id,
            'mod_id' => $modId,
            'type' => VehicleTypeEnum::PC->value,
            'year_from' => 2013,
            'description' => '1.4 TSI',
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => $provider->value,
            'allow_change_fields' => ['year_from', 'year_to'],
        ]);
    }

    /**
     * @param  list<array{eng_id: int, relation_provider: string}>  $engines
     * @return array{
     *     user_id: int,
     *     operation_id: string,
     *     operation: string,
     *     modification: array{
     *         mod_id: int,
     *         ms_id: int,
     *         type: string,
     *         year_from: int,
     *         description: string,
     *         power_ps: int,
     *         power_kw: int,
     *         engine_type: string,
     *         provider: string,
     *         allow_change_fields: list<string>,
     *         engines: list<array{eng_id: int, relation_provider: string}>
     *     }
     * }
     */
    private function modificationUpdatePayload(
        string $operationId,
        Vehicle $vehicle,
        Modification $modification,
        array $engines,
    ): array {
        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => 'update',
            'modification' => [
                'mod_id' => $modification->mod_id,
                'ms_id' => $vehicle->ms_id,
                'type' => VehicleTypeEnum::PC->value,
                'year_from' => 2013,
                'description' => '1.4 TSI',
                'power_ps' => 150,
                'power_kw' => 110,
                'engine_type' => EngineTypeEnum::PETROL->value,
                'provider' => $modification->provider->value,
                'allow_change_fields' => ['year_from', 'year_to'],
                'engines' => $engines,
            ],
        ];
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
                'year_from' => 2019,
                'description' => $description,
                'power_ps' => 150,
                'power_kw' => 110,
                'engine_type' => EngineTypeEnum::PETROL->value,
                'provider' => ProviderEnum::OD->value,
                'allow_change_fields' => [],
            ],
        ];
    }
}
