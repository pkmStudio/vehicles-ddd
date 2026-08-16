<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Messaging\Handlers\PartSpecificationMutationRequestedHandler;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

final class PartSpecificationMutationRequestedHandlerTest extends TestCase
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

    public function test_vehicle_owner_part_specification_create_update_and_delete_messages(): void
    {
        $this->createManufacturer(900);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(3)
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->status === CatalogMutationStatusEnum::Completed));

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-create-1',
            operation: 'create',
            specificationId: 8001,
            ownerExternalId: 7001,
            vehicleName: 'Owner Vehicle',
            specificationName: 'Front wiper',
        ));

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 7001,
            'name' => 'Owner Vehicle',
        ]);
        $this->assertDatabaseHas('part_specifications', [
            'id' => 8001,
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'template' => DetailTemplateEnum::WIPER->value,
            'name' => 'Front wiper',
        ]);

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-update-1',
            operation: 'update',
            specificationId: 8001,
            ownerExternalId: 7001,
            vehicleName: 'Owner Vehicle Updated',
            specificationName: 'Updated front wiper',
        ));

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 7001,
            'name' => 'Owner Vehicle Updated',
        ]);
        $this->assertDatabaseHas('part_specifications', [
            'id' => 8001,
            'name' => 'Updated front wiper',
        ]);

        app(PartSpecificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'part-specification-delete-1',
            'operation' => 'delete',
            'part_specification' => [
                'id' => 8001,
            ],
        ]);

        $this->assertDatabaseMissing('part_specifications', ['id' => 8001]);
    }

    public function test_vehicle_owner_part_specification_create_message_allows_missing_id(): void
    {
        $this->createManufacturer(900);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->operationId === 'part-specification-create-without-id'));

        $payload = $this->vehicleSpecificationPayload(
            operationId: 'part-specification-create-without-id',
            operation: 'create',
            specificationId: 8002,
            ownerExternalId: 7002,
            vehicleName: 'Owner Vehicle Without Spec Id',
            specificationName: 'Front wiper without spec id',
        );
        unset($payload['part_specification']['id']);

        app(PartSpecificationMutationRequestedHandler::class)->handle($payload);

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 7002,
            'name' => 'Owner Vehicle Without Spec Id',
        ]);
        $this->assertDatabaseHas('part_specifications', [
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'template' => DetailTemplateEnum::WIPER->value,
            'name' => 'Front wiper without spec id',
        ]);
    }

    public function test_vehicle_wiper_details_are_normalized_before_write(): void
    {
        $this->createManufacturer(900);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Completed
                && $result->operationId === 'part-specification-details-normalized'));

        $payload = $this->vehicleSpecificationPayload(
            operationId: 'part-specification-details-normalized',
            operation: 'create',
            specificationId: 8701,
            ownerExternalId: 7701,
            vehicleName: 'Details Normalized Owner',
            specificationName: 'Normalized front wiper',
        );
        $payload['part_specification']['details'] = [
            'position' => 'front',
            'front' => [
                'length' => 650,
                'adapter_type_front' => ['A1', '', null],
                'comment' => '',
            ],
        ];

        app(PartSpecificationMutationRequestedHandler::class)->handle($payload);

        $details = PartSpecification::query()->findOrFail(8701)->details;

        $this->assertSame([
            'front' => [
                'length' => 650,
                'adapter_type_front' => ['A1'],
            ],
        ], $details);
    }

    public function test_accepts_dan_center_vehicle_rest_part_specification_sample_payloads(): void
    {
        $manufacturer = $this->createManufacturer(900);
        $vehicle = $this->createVehicle(msId: 7001, manufacturer: $manufacturer, name: 'Original Vehicle');

        PartSpecification::query()->create([
            'id' => 10,
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => $vehicle->id,
            'template' => DetailTemplateEnum::WIPER->value,
            'details' => [
                'front' => ['length_main' => ['min' => 600]],
            ],
            'name' => 'Original front wiper',
        ]);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(2)
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->status === CatalogMutationStatusEnum::Completed));

        app(PartSpecificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'dan-center-part-specification-update-sample',
            'operation' => 'update',
            'part_specification' => [
                'id' => 10,
                'owner' => [
                    'type' => 'vehicle',
                    'external_id' => 7001,
                ],
                'template' => 'wiper',
                'details' => [
                    'front' => ['length_main' => ['min' => 650]],
                ],
                'name' => 'Updated front wiper',
            ],
        ]);

        app(PartSpecificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'dan-center-part-specification-create-sample',
            'operation' => 'create',
            'part_specification' => [
                'owner' => [
                    'type' => 'vehicle',
                    'external_id' => 7001,
                ],
                'template' => 'wiper',
                'details' => [
                    'back' => ['length_rear' => ['min' => 350]],
                ],
                'name' => 'New rear wiper',
            ],
        ]);

        $this->assertDatabaseHas('part_specifications', [
            'id' => 10,
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => $vehicle->id,
            'template' => DetailTemplateEnum::WIPER->value,
            'name' => 'Updated front wiper',
        ]);
        $this->assertDatabaseHas('part_specifications', [
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => $vehicle->id,
            'template' => DetailTemplateEnum::WIPER->value,
            'name' => 'New rear wiper',
        ]);
    }

    public function test_invalid_vehicle_wiper_details_are_rejected_before_owner_resolution(): void
    {
        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::InvalidDetails->value
                && $result->errors[0]['rule'] === 'single_side'));

        $payload = $this->vehicleSpecificationPayload(
            operationId: 'part-specification-invalid-details-before-owner',
            operation: 'create',
            specificationId: 8702,
            ownerExternalId: 7702,
            vehicleName: 'Invalid Details Owner',
            specificationName: 'Invalid details spec',
            mfaId: 999,
        );
        $payload['part_specification']['details'] = [
            'front' => ['length' => 650],
            'back' => ['length' => 350],
        ];

        app(PartSpecificationMutationRequestedHandler::class)->handle($payload);

        $this->assertDatabaseMissing('vehicles', ['ms_id' => 7702]);
        $this->assertDatabaseMissing('part_specifications', ['id' => 8702]);
    }

    public function test_engine_owner_part_specification_create_message_creates_engine_owner(): void
    {
        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Completed));

        app(PartSpecificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'engine-part-specification-create-1',
            'operation' => 'create',
            'part_specification' => [
                'id' => 8101,
                'owner' => [
                    'type' => PartableTypeEnum::ENGINE->value,
                    'external_id' => 7101,
                    'engine' => [
                        'code_engine' => 'ABC',
                        'power_kw_start' => 100,
                        'power_ps_start' => 136,
                        'fuel_type' => EngineFuelTypeEnum::PETROL->value,
                        'engine_capacity' => '2.0',
                        'provider' => ProviderEnum::TD->value,
                    ],
                ],
                'template' => DetailTemplateEnum::SPARK_PLUGS->value,
                'details' => ['thread' => ['diameter' => 'M14']],
                'name' => 'Spark plug',
            ],
        ]);

        $this->assertDatabaseHas('engines', [
            'eng_id' => 7101,
            'code_engine' => 'ABC',
        ]);
        $this->assertDatabaseHas('part_specifications', [
            'id' => 8101,
            'partable_type' => PartableTypeEnum::ENGINE->value,
            'template' => DetailTemplateEnum::SPARK_PLUGS->value,
            'name' => 'Spark plug',
        ]);
    }

    public function test_part_specification_create_is_rejected_when_owner_is_missing_without_payload(): void
    {
        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::OwnerNotFound->value));

        app(PartSpecificationMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'part-specification-owner-missing-1',
            'operation' => 'create',
            'part_specification' => [
                'id' => 8201,
                'owner' => [
                    'type' => PartableTypeEnum::VEHICLE->value,
                    'external_id' => 7201,
                ],
                'template' => DetailTemplateEnum::WIPER->value,
                'details' => ['front' => ['length' => 650]],
            ],
        ]);

        $this->assertDatabaseMissing('part_specifications', ['id' => 8201]);
    }

    public function test_part_specification_create_is_rejected_when_id_already_exists(): void
    {
        $this->createManufacturer(900);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Completed));
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::AlreadyExists->value));

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-create-duplicate-source-1',
            operation: 'create',
            specificationId: 8301,
            ownerExternalId: 7301,
            vehicleName: 'Duplicate Source',
            specificationName: 'Original spec',
        ));

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-create-duplicate-1',
            operation: 'create',
            specificationId: 8301,
            ownerExternalId: 7301,
            vehicleName: 'Duplicate Source',
            specificationName: 'Duplicate spec',
        ));

        $this->assertDatabaseHas('part_specifications', [
            'id' => 8301,
            'name' => 'Original spec',
        ]);
    }

    public function test_part_specification_create_is_rejected_when_owner_template_and_details_already_exist(): void
    {
        $this->createManufacturer(900);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Completed));
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::AlreadyExists->value
                && $result->errors[0]['rule'] === 'unique'));

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-create-natural-duplicate-source',
            operation: 'create',
            specificationId: 8351,
            ownerExternalId: 7351,
            vehicleName: 'Natural Duplicate Source',
            specificationName: 'Original natural spec',
        ));

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-create-natural-duplicate',
            operation: 'create',
            specificationId: 8352,
            ownerExternalId: 7351,
            vehicleName: 'Natural Duplicate Source',
            specificationName: 'Duplicate natural spec',
        ));

        $this->assertDatabaseHas('part_specifications', [
            'id' => 8351,
            'name' => 'Original natural spec',
        ]);
        $this->assertDatabaseMissing('part_specifications', [
            'id' => 8352,
        ]);
    }

    public function test_part_specification_update_is_rejected_when_specification_not_found(): void
    {
        $this->createManufacturer(900);

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Update
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::NotFound->value));

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-update-missing-1',
            operation: 'update',
            specificationId: 8401,
            ownerExternalId: 7401,
            vehicleName: 'Missing spec owner',
            specificationName: 'Missing spec',
        ));

        $this->assertDatabaseMissing('part_specifications', ['id' => 8401]);
    }

    public function test_part_specification_create_is_rejected_when_vehicle_owner_manufacturer_is_missing(): void
    {
        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::ManufacturerNotFound->value));

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-owner-manufacturer-missing-1',
            operation: 'create',
            specificationId: 8501,
            ownerExternalId: 7501,
            vehicleName: 'No Manufacturer',
            specificationName: 'Rejected spec',
        ));

        $this->assertDatabaseMissing('vehicles', ['ms_id' => 7501]);
        $this->assertDatabaseMissing('part_specifications', ['id' => 8501]);
    }

    public function test_vehicle_owner_payload_rejects_provider_conflict_for_existing_td_vehicle(): void
    {
        $existingManufacturer = $this->createManufacturer(901);
        $incomingManufacturer = $this->createManufacturer(902);
        $existingParent = $this->createVehicle(
            msId: 7601,
            manufacturer: $existingManufacturer,
            provider: ProviderEnum::TD,
        );
        $incomingParent = $this->createVehicle(
            msId: 7602,
            manufacturer: $incomingManufacturer,
            provider: ProviderEnum::OD,
        );
        $this->createVehicle(
            msId: 7603,
            manufacturer: $existingManufacturer,
            parentId: $existingParent->id,
            name: 'TD owner old',
            provider: ProviderEnum::TD,
            generation: 'TD owner generation',
            typeCarcase: CarcaseTypeEnum::SALOON,
        );

        $notifier = $this->mock(CatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (CatalogMutationResultDTO $result): bool => $result->entity === CatalogEntityEnum::PartSpecification
                && $result->operation === CatalogMutationOperationEnum::Create
                && $result->status === CatalogMutationStatusEnum::Rejected
                && $result->reason === CatalogMutationRejectReasonEnum::ProviderOwnershipConflict->value
                && $result->operationId === 'part-specification-owner-td-locked'));

        app(PartSpecificationMutationRequestedHandler::class)->handle($this->vehicleSpecificationPayload(
            operationId: 'part-specification-owner-td-locked',
            operation: 'create',
            specificationId: 8601,
            ownerExternalId: 7603,
            vehicleName: 'TD owner new',
            specificationName: 'TD owner spec',
            mfaId: $incomingManufacturer->mfa_id,
            provider: ProviderEnum::OD,
            parentMsId: $incomingParent->ms_id,
            generation: 'Incoming owner generation',
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
        ));

        $this->assertDatabaseHas('vehicles', [
            'ms_id' => 7603,
            'manufacturer_id' => $existingManufacturer->id,
            'mfa_id' => $existingManufacturer->mfa_id,
            'parent_id' => $existingParent->id,
            'name' => 'TD owner old',
            'generation' => 'TD owner generation',
            'type_carcase' => CarcaseTypeEnum::SALOON->value,
            'provider' => ProviderEnum::TD->value,
        ]);
        $this->assertDatabaseMissing('part_specifications', ['id' => 8601]);
    }

    public function test_part_specification_mutation_events_have_unique_names_and_same_handler(): void
    {
        $this->assertSame([PartSpecificationMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.PART_SPECIFICATION_CREATE_REQUESTED'));
        $this->assertSame([PartSpecificationMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.PART_SPECIFICATION_UPDATE_REQUESTED'));
        $this->assertSame([PartSpecificationMutationRequestedHandler::class, 'handle'], config('rabbit-transport.inbound.PART_SPECIFICATION_DELETE_REQUESTED'));
    }

    private function createManufacturer(int $mfaId): Manufacturer
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
    private function vehicleSpecificationPayload(
        string $operationId,
        string $operation,
        int $specificationId,
        int $ownerExternalId,
        string $vehicleName,
        string $specificationName,
        int $mfaId = 900,
        ProviderEnum $provider = ProviderEnum::OD,
        ?int $parentMsId = null,
        string $generation = 'III',
        CarcaseTypeEnum $typeCarcase = CarcaseTypeEnum::HATCHBACK,
    ): array {
        $vehicle = [
            'mfa_id' => $mfaId,
            'name' => $vehicleName,
            'generation' => $generation,
            'generation_year_from' => 2013,
            'type' => VehicleTypeEnum::PC->value,
            'type_carcase' => $typeCarcase->value,
            'provider' => $provider->value,
            'steering_type' => SteeringTypeEnum::LEFT->value,
            'is_allow' => true,
        ];

        if ($parentMsId !== null) {
            $vehicle['parent_ms_id'] = $parentMsId;
        }

        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'part_specification' => [
                'id' => $specificationId,
                'owner' => [
                    'type' => PartableTypeEnum::VEHICLE->value,
                    'external_id' => $ownerExternalId,
                    'vehicle' => $vehicle,
                ],
                'template' => DetailTemplateEnum::WIPER->value,
                'details' => ['front' => ['length' => 650]],
                'name' => $specificationName,
            ],
        ];
    }
}
