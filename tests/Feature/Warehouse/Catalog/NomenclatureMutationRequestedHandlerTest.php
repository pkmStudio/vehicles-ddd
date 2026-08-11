<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers\NomenclatureMutationRequestedHandler;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\NomenclatureMutationPayload as WireNomenclatureMutationPayload;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\NomenclatureMutationRequested as WireNomenclatureMutationRequested;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\CatalogMutationOperation as WireCatalogMutationOperation;
use Tests\TestCase;

/**
 * Покрывает RabbitMQ CRUD-сценарий Warehouse/Catalog для номенклатуры.
 */
final class NomenclatureMutationRequestedHandlerTest extends TestCase
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

    public function test_nomenclature_create_update_and_delete_messages(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create($this->brandAttributes());

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(3)
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Nomenclature
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed));

        app(NomenclatureMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-nomenclature-create-1',
            operation: 'create',
            typeId: $type->id,
            brandId: $brand->id,
            partNumber: 'VB-1',
        ));

        $nomenclature = Nomenclature::query()->where('part_number', 'VB-1')->firstOrFail();

        app(NomenclatureMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-nomenclature-update-1',
            operation: 'update',
            typeId: $type->id,
            brandId: $brand->id,
            partNumber: 'VB-2',
            id: $nomenclature->id,
        ));

        app(NomenclatureMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'warehouse-nomenclature-delete-1',
            'operation' => 'delete',
            'nomenclature' => [
                'id' => $nomenclature->id,
            ],
        ]);

        $this->assertDatabaseMissing('nomenclatures', ['id' => $nomenclature->id]);
    }

    public function test_nomenclature_create_message_accepts_published_wire_contract_payload(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create($this->brandAttributes());

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Nomenclature
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed
                && $result->operationId === 'warehouse-nomenclature-wire-contract'
                && $result->recordId !== null));

        $message = new WireNomenclatureMutationRequested(
            userId: 42,
            operationId: 'warehouse-nomenclature-wire-contract',
            operation: WireCatalogMutationOperation::Create->value,
            nomenclature: new WireNomenclatureMutationPayload(
                typeId: $type->id,
                brandId: $brand->id,
                name: 'Wire Part',
                country: 'RU',
                partNumber: 'WIRE-1',
                color: 'Black',
                weight: 100,
                material: ['Rubber'],
                vehicleType: ['PC'],
                quantityPak: 1,
                quantityInPak: 1,
                details: [],
            ),
        );

        app(NomenclatureMutationRequestedHandler::class)->handle($message->toArray());

        $this->assertDatabaseHas('nomenclatures', [
            'type_id' => $type->id,
            'brand_id' => $brand->id,
            'part_number' => 'WIRE-1',
            'name' => 'Wire Part',
        ]);
    }

    public function test_nomenclature_create_is_rejected_when_type_is_missing(): void
    {
        $brand = Brand::query()->create($this->brandAttributes());

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Nomenclature
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Rejected
                && $result->reason === WarehouseCatalogMutationRejectReasonEnum::TypeNotFound->value));

        app(NomenclatureMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-nomenclature-type-missing-1',
            operation: 'create',
            typeId: 999,
            brandId: $brand->id,
            partNumber: 'VB-MISSING-TYPE',
        ));

        $this->assertDatabaseMissing('nomenclatures', ['part_number' => 'VB-MISSING-TYPE']);
    }

    public function test_nomenclature_create_is_rejected_when_brand_is_missing(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Nomenclature
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Rejected
                && $result->reason === WarehouseCatalogMutationRejectReasonEnum::BrandNotFound->value));

        app(NomenclatureMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-nomenclature-brand-missing-1',
            operation: 'create',
            typeId: $type->id,
            brandId: 999,
            partNumber: 'VB-MISSING-BRAND',
        ));

        $this->assertDatabaseMissing('nomenclatures', ['part_number' => 'VB-MISSING-BRAND']);
    }

    public function test_nomenclature_create_is_rejected_when_part_number_already_exists(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create($this->brandAttributes());
        Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $brand->id, 'VB-DUP'));

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Nomenclature
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Rejected
                && $result->reason === WarehouseCatalogMutationRejectReasonEnum::AlreadyExists->value));

        app(NomenclatureMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-nomenclature-duplicate-1',
            operation: 'create',
            typeId: $type->id,
            brandId: $brand->id,
            partNumber: 'VB-DUP',
        ));

        $this->assertSame(1, Nomenclature::query()->where('part_number', 'VB-DUP')->count());
    }

    public function test_nomenclature_delete_detaches_dependencies(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create($this->brandAttributes());
        $packDimension = PackDimension::query()->create($this->packDimensionAttributes($type->id));
        $nomenclature = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $brand->id, 'VB-BLOCKED'));
        $kit = Kit::query()->create($this->kitAttributes($type->id, $packDimension->id));

        DB::table('kit_nomenclature')->insert([
            'kit_id' => $kit->id,
            'nomenclature_id' => $nomenclature->id,
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('nomenclature_integrations')->insert([
            'nomenclature_id' => $nomenclature->id,
            'provider' => 'test',
            'sync_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Nomenclature
                && $result->operation === WarehouseCatalogMutationOperationEnum::Delete
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed));

        app(NomenclatureMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'warehouse-nomenclature-delete-detach-1',
            'operation' => 'delete',
            'nomenclature' => [
                'id' => $nomenclature->id,
            ],
        ]);

        $this->assertDatabaseMissing('nomenclatures', ['id' => $nomenclature->id]);
        $this->assertDatabaseMissing('kit_nomenclature', ['nomenclature_id' => $nomenclature->id]);
        $this->assertDatabaseHas('kits', ['id' => $kit->id]);
        $this->assertDatabaseHas('nomenclature_integrations', [
            'provider' => 'test',
            'nomenclature_id' => null,
        ]);
    }

    public function test_nomenclature_delete_allows_moysklad_integration_and_detaches_it(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create($this->brandAttributes());
        $nomenclature = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $brand->id, 'VB-MS'));

        DB::table('nomenclature_integrations')->insert([
            'nomenclature_id' => $nomenclature->id,
            'provider' => 'moysklad',
            'external_id' => '44444444-4444-4444-4444-444444444444',
            'external_code' => "nomenclature:{$nomenclature->id}",
            'sync_status' => 'synced',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Nomenclature
                && $result->operation === WarehouseCatalogMutationOperationEnum::Delete
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed));

        app(NomenclatureMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'warehouse-nomenclature-delete-moysklad-1',
            'operation' => 'delete',
            'nomenclature' => [
                'id' => $nomenclature->id,
            ],
        ]);

        $this->assertDatabaseMissing('nomenclatures', ['id' => $nomenclature->id]);
        $this->assertDatabaseHas('nomenclature_integrations', [
            'provider' => 'moysklad',
            'external_id' => '44444444-4444-4444-4444-444444444444',
            'nomenclature_id' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        string $operationId,
        string $operation,
        int $typeId,
        int $brandId,
        string $partNumber,
        ?int $id = null,
    ): array {
        $nomenclature = $this->nomenclatureAttributes($typeId, $brandId, $partNumber);

        if ($id !== null) {
            $nomenclature['id'] = $id;
        }

        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'nomenclature' => $nomenclature,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function brandAttributes(): array
    {
        return [
            'name' => 'Bosch',
            'number_sert' => 'CERT-1',
            'date_start' => '2026-01-01 00:00:00',
            'date_end' => '2026-12-31 00:00:00',
            'char' => 'B',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nomenclatureAttributes(int $typeId, int $brandId, string $partNumber): array
    {
        return [
            'type_id' => $typeId,
            'brand_id' => $brandId,
            'name' => "Part {$partNumber}",
            'country' => 'RU',
            'part_number' => $partNumber,
            'color' => 'Black',
            'weight' => 100,
            'material' => ['Rubber'],
            'vehicle_type' => ['PC'],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function packDimensionAttributes(int $typeId): array
    {
        return [
            'name' => 'Box S',
            'weight' => 10,
            'width' => 10,
            'height' => 5,
            'length' => 20,
            'price' => 15,
            'type_id' => $typeId,
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
