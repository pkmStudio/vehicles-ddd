<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Catalog;

use App\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationStatusEnum;
use App\Vehicles\Catalog\Infrastructure\Messaging\Handlers\PartSpecificationMutationRequestedHandler;
use App\Vehicles\Catalog\Infrastructure\Models\Manufacturer;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
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
                        'engine_capacity' => '2.0',
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
    ): array {
        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'part_specification' => [
                'id' => $specificationId,
                'owner' => [
                    'type' => PartableTypeEnum::VEHICLE->value,
                    'external_id' => $ownerExternalId,
                    'vehicle' => [
                        'mfa_id' => 900,
                        'name' => $vehicleName,
                        'type' => VehicleTypeEnum::PC->value,
                        'type_carcase' => CarcaseTypeEnum::HATCHBACK->value,
                        'provider' => ProviderEnum::OD->value,
                        'steering_type' => SteeringTypeEnum::LEFT->value,
                    ],
                ],
                'template' => DetailTemplateEnum::WIPER->value,
                'details' => ['front' => ['length' => 650]],
                'name' => $specificationName,
            ],
        ];
    }
}
