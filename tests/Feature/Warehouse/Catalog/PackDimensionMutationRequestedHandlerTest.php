<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers\PackDimensionMutationRequestedHandler;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\PackDimensionMutationPayload as WirePackDimensionMutationPayload;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\PackDimensionMutationRequested as WirePackDimensionMutationRequested;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\CatalogMutationOperation as WireCatalogMutationOperation;
use Tests\TestCase;

/**
 * Покрывает RabbitMQ CRUD-сценарий Warehouse/Catalog для упаковочных размеров.
 */
final class PackDimensionMutationRequestedHandlerTest extends TestCase
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

    public function test_pack_dimension_create_update_and_delete_messages(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(3)
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::PackDimension
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed));

        app(PackDimensionMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-pack-dimension-create-1',
            operation: 'create',
            typeId: $type->id,
            name: 'Box S',
        ));

        $packDimension = PackDimension::query()->where('name', 'Box S')->firstOrFail();

        app(PackDimensionMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-pack-dimension-update-1',
            operation: 'update',
            typeId: $type->id,
            name: 'Box M',
            id: $packDimension->id,
        ));

        app(PackDimensionMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'warehouse-pack-dimension-delete-1',
            'operation' => 'delete',
            'pack_dimension' => [
                'id' => $packDimension->id,
            ],
        ]);

        $this->assertDatabaseMissing('pack_dimensions', ['id' => $packDimension->id]);
    }

    public function test_pack_dimension_create_message_accepts_published_wire_contract_payload(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::PackDimension
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed
                && $result->operationId === 'warehouse-pack-dimension-wire-contract'
                && $result->recordId !== null));

        $message = new WirePackDimensionMutationRequested(
            userId: 42,
            operationId: 'warehouse-pack-dimension-wire-contract',
            operation: WireCatalogMutationOperation::Create->value,
            packDimension: new WirePackDimensionMutationPayload(
                name: 'Wire Box',
                weight: 20,
                width: 11,
                height: 6,
                length: 21,
                price: 16,
                typeId: $type->id,
                generated: false,
            ),
        );

        app(PackDimensionMutationRequestedHandler::class)->handle($message->toArray());

        $this->assertDatabaseHas('pack_dimensions', [
            'name' => 'Wire Box',
            'type_id' => $type->id,
            'generated' => false,
        ]);
    }

    public function test_pack_dimension_create_is_rejected_when_type_is_missing(): void
    {
        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::PackDimension
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Rejected
                && $result->reason === WarehouseCatalogMutationRejectReasonEnum::TypeNotFound->value));

        app(PackDimensionMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-pack-dimension-type-missing-1',
            operation: 'create',
            typeId: 999,
            name: 'Missing Type Box',
        ));

        $this->assertDatabaseMissing('pack_dimensions', ['name' => 'Missing Type Box']);
    }

    public function test_pack_dimension_delete_cascades_related_kits(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $packDimension = PackDimension::query()->create($this->packDimensionAttributes($type->id, 'Box Blocked'));
        $kit = Kit::query()->create($this->kitAttributes($type->id, $packDimension->id));

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::PackDimension
                && $result->operation === WarehouseCatalogMutationOperationEnum::Delete
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed));

        app(PackDimensionMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'warehouse-pack-dimension-delete-cascade-1',
            'operation' => 'delete',
            'pack_dimension' => [
                'id' => $packDimension->id,
            ],
        ]);

        $this->assertDatabaseMissing('pack_dimensions', ['id' => $packDimension->id]);
        $this->assertDatabaseMissing('kits', ['id' => $kit->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        string $operationId,
        string $operation,
        int $typeId,
        string $name,
        ?int $id = null,
    ): array {
        $packDimension = $this->packDimensionAttributes($typeId, $name);

        if ($id !== null) {
            $packDimension['id'] = $id;
        }

        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'pack_dimension' => $packDimension,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function packDimensionAttributes(int $typeId, string $name): array
    {
        return [
            'name' => $name,
            'weight' => 10,
            'width' => 10,
            'height' => 5,
            'length' => 20,
            'price' => 15,
            'type_id' => $typeId,
            'generated' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kitAttributes(int $typeId, int $packDimensionId): array
    {
        return [
            'complectation' => 'Kit',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => false,
            'weight' => 100,
            'pack_dimension_id' => $packDimensionId,
            'type_id' => $typeId,
            'import_hash' => 'hash-blocked',
            'is_sale_separately' => false,
            'is_active' => true,
        ];
    }
}
