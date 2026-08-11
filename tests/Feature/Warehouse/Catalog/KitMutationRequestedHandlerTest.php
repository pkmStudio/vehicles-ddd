<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers\BrandMutationRequestedHandler;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers\KitMutationRequestedHandler;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers\NomenclatureMutationRequestedHandler;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers\PackDimensionMutationRequestedHandler;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\KitMutationPayload as WireKitMutationPayload;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\KitMutationRequested as WireKitMutationRequested;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\CatalogMutationOperation as WireCatalogMutationOperation;
use PkmStudio\DanWireContracts\Vehicles\Shared\Results\DTO\CatalogMutationCompleted as WireCatalogMutationCompleted;
use Tests\TestCase;

/**
 * Покрывает RabbitMQ CRUD-сценарий Warehouse/Catalog для наборов.
 */
final class KitMutationRequestedHandlerTest extends TestCase
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

    public function test_warehouse_catalog_mutation_result_matches_published_wire_contract(): void
    {
        $result = new WarehouseCatalogMutationResultDTO(
            userId: 42,
            operationId: 'warehouse-result-wire-contract',
            entity: WarehouseCatalogEntityEnum::Kit,
            operation: WarehouseCatalogMutationOperationEnum::Create,
            status: WarehouseCatalogMutationStatusEnum::Completed,
            recordId: 1001,
            businessKey: 'hash-wire-contract',
            errors: [],
        );

        $wirePayload = WireCatalogMutationCompleted::fromArray($result->toArray())->toArray();

        self::assertSame([
            'user_id' => 42,
            'operation_id' => 'warehouse-result-wire-contract',
            'entity' => 'kit',
            'operation' => 'create',
            'status' => 'completed',
            'record_id' => 1001,
            'business_key' => 'hash-wire-contract',
            'errors' => [],
        ], $wirePayload);
    }

    public function test_kit_create_update_and_delete_messages_manage_pivot_manually(): void
    {
        [$type, $brand, $packDimension] = $this->createBaseCatalog();
        $first = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $brand->id, 'VB-1'));
        $second = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $brand->id, 'VB-2'));
        $third = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $brand->id, 'VB-3'));

        $kitProperties = $this->mock(KitPropertiesClientInterface::class);
        $kitProperties->shouldReceive('build')
            ->twice()
            ->andReturn(
                new KitPropertiesDTO(
                    typeId: $type->id,
                    packDimensionId: $packDimension->id,
                    weight: 200.0,
                    quantityInPackage: 2,
                    quantityPackage: 1,
                    complectation: 'VB-1;VB-2',
                    importHash: 'hash-create',
                ),
                new KitPropertiesDTO(
                    typeId: $type->id,
                    packDimensionId: $packDimension->id,
                    weight: 200.0,
                    quantityInPackage: 2,
                    quantityPackage: 1,
                    complectation: 'VB-3;VB-1',
                    importHash: 'hash-update',
                ),
            );

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(3)
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Kit
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed));

        app(KitMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-kit-create-1',
            operation: 'create',
            nomenclatureIds: [$first->id, $second->id],
        ));

        $kit = Kit::query()->where('import_hash', 'hash-create')->firstOrFail();
        $this->assertDatabaseHas('kit_nomenclature', ['kit_id' => $kit->id, 'nomenclature_id' => $first->id, 'sort' => 0]);
        $this->assertDatabaseHas('kit_nomenclature', ['kit_id' => $kit->id, 'nomenclature_id' => $second->id, 'sort' => 1]);

        app(KitMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-kit-update-1',
            operation: 'update',
            nomenclatureIds: [$third->id, $first->id],
            id: $kit->id,
        ));

        $this->assertDatabaseMissing('kit_nomenclature', ['kit_id' => $kit->id, 'nomenclature_id' => $second->id]);
        $this->assertDatabaseHas('kit_nomenclature', ['kit_id' => $kit->id, 'nomenclature_id' => $third->id, 'sort' => 0]);
        $this->assertDatabaseHas('kit_nomenclature', ['kit_id' => $kit->id, 'nomenclature_id' => $first->id, 'sort' => 1]);

        app(KitMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'warehouse-kit-delete-1',
            'operation' => 'delete',
            'kit' => [
                'id' => $kit->id,
            ],
        ]);

        $this->assertDatabaseMissing('kits', ['id' => $kit->id]);
        $this->assertDatabaseMissing('kit_nomenclature', ['kit_id' => $kit->id]);
    }

    public function test_kit_create_message_accepts_published_wire_contract_payload(): void
    {
        [$type, $brand, $packDimension] = $this->createBaseCatalog();
        $nomenclature = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $brand->id, 'WIRE-KIT-1'));

        $kitProperties = $this->mock(KitPropertiesClientInterface::class);
        $kitProperties->shouldReceive('build')
            ->once()
            ->andReturn(new KitPropertiesDTO(
                typeId: $type->id,
                packDimensionId: $packDimension->id,
                weight: 100.0,
                quantityInPackage: 1,
                quantityPackage: 1,
                complectation: 'WIRE-KIT-1',
                importHash: 'hash-wire-contract',
            ));

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Kit
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed
                && $result->operationId === 'warehouse-kit-wire-contract'
                && $result->recordId !== null));

        $message = new WireKitMutationRequested(
            userId: 42,
            operationId: 'warehouse-kit-wire-contract',
            operation: WireCatalogMutationOperation::Create->value,
            kit: new WireKitMutationPayload(
                nomenclatureIds: [$nomenclature->id],
                isSaleSeparately: true,
                isActive: true,
                guarantee: 12,
            ),
        );

        app(KitMutationRequestedHandler::class)->handle($message->toArray());

        $kit = Kit::query()->where('import_hash', 'hash-wire-contract')->firstOrFail();

        $this->assertDatabaseHas('kit_nomenclature', [
            'kit_id' => $kit->id,
            'nomenclature_id' => $nomenclature->id,
            'sort' => 0,
        ]);
    }

    public function test_kit_create_is_rejected_when_nomenclature_is_missing(): void
    {
        $kitProperties = $this->mock(KitPropertiesClientInterface::class);
        $kitProperties->shouldNotReceive('build');

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Kit
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Rejected
                && $result->reason === WarehouseCatalogMutationRejectReasonEnum::NomenclatureNotFound->value
                && ($result->errors['missing_ids'][0] ?? null) === 999));

        app(KitMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-kit-missing-nomenclature-1',
            operation: 'create',
            nomenclatureIds: [999],
        ));

        $this->assertSame(0, Kit::query()->count());
    }

    public function test_kit_create_is_rejected_when_import_hash_already_exists(): void
    {
        [$type, $brand, $packDimension] = $this->createBaseCatalog();
        $nomenclature = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $brand->id, 'VB-1'));
        Kit::query()->create($this->kitAttributes($type->id, $packDimension->id, 'hash-duplicate'));

        $kitProperties = $this->mock(KitPropertiesClientInterface::class);
        $kitProperties->shouldReceive('build')
            ->once()
            ->andReturn(new KitPropertiesDTO(
                typeId: $type->id,
                packDimensionId: $packDimension->id,
                weight: 100.0,
                quantityInPackage: 1,
                quantityPackage: 1,
                complectation: 'VB-1',
                importHash: 'hash-duplicate',
            ));

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Kit
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Rejected
                && $result->reason === WarehouseCatalogMutationRejectReasonEnum::AlreadyExists->value));

        app(KitMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-kit-duplicate-1',
            operation: 'create',
            nomenclatureIds: [$nomenclature->id],
        ));

        $this->assertSame(1, Kit::query()->where('import_hash', 'hash-duplicate')->count());
    }

    public function test_kit_create_is_rejected_when_real_composition_validation_fails(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $firstBrand = Brand::query()->create($this->brandAttributes());
        $secondBrand = Brand::query()->create([
            ...$this->brandAttributes(),
            'name' => 'Denso',
            'number_sert' => 'CERT-2',
            'char' => 'D',
        ]);

        $first = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $firstBrand->id, 'VB-1'));
        $second = Nomenclature::query()->create($this->nomenclatureAttributes($type->id, $secondBrand->id, 'VB-2'));

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Kit
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Rejected
                && $result->reason === WarehouseCatalogMutationRejectReasonEnum::InvalidComposition->value
                && str_contains((string) ($result->errors['message'] ?? ''), 'Нельзя собрать комплект из разных брендов')));

        app(KitMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-kit-invalid-composition-1',
            operation: 'create',
            nomenclatureIds: [$first->id, $second->id],
        ));

        $this->assertSame(0, Kit::query()->count());
    }

    public function test_warehouse_catalog_mutation_events_are_registered(): void
    {
        $brandHandler = [BrandMutationRequestedHandler::class, 'handle'];
        $nomenclatureHandler = [NomenclatureMutationRequestedHandler::class, 'handle'];
        $packDimensionHandler = [PackDimensionMutationRequestedHandler::class, 'handle'];
        $kitHandler = [KitMutationRequestedHandler::class, 'handle'];

        $this->assertSame($brandHandler, config('rabbit-transport.inbound.WAREHOUSE_BRAND_CREATE_REQUESTED'));
        $this->assertSame($brandHandler, config('rabbit-transport.inbound.WAREHOUSE_BRAND_UPDATE_REQUESTED'));
        $this->assertSame($brandHandler, config('rabbit-transport.inbound.WAREHOUSE_BRAND_DELETE_REQUESTED'));
        $this->assertSame($nomenclatureHandler, config('rabbit-transport.inbound.WAREHOUSE_NOMENCLATURE_CREATE_REQUESTED'));
        $this->assertSame($nomenclatureHandler, config('rabbit-transport.inbound.WAREHOUSE_NOMENCLATURE_UPDATE_REQUESTED'));
        $this->assertSame($nomenclatureHandler, config('rabbit-transport.inbound.WAREHOUSE_NOMENCLATURE_DELETE_REQUESTED'));
        $this->assertSame($packDimensionHandler, config('rabbit-transport.inbound.WAREHOUSE_PACK_DIMENSION_CREATE_REQUESTED'));
        $this->assertSame($packDimensionHandler, config('rabbit-transport.inbound.WAREHOUSE_PACK_DIMENSION_UPDATE_REQUESTED'));
        $this->assertSame($packDimensionHandler, config('rabbit-transport.inbound.WAREHOUSE_PACK_DIMENSION_DELETE_REQUESTED'));
        $this->assertSame($kitHandler, config('rabbit-transport.inbound.WAREHOUSE_KIT_CREATE_REQUESTED'));
        $this->assertSame($kitHandler, config('rabbit-transport.inbound.WAREHOUSE_KIT_UPDATE_REQUESTED'));
        $this->assertSame($kitHandler, config('rabbit-transport.inbound.WAREHOUSE_KIT_DELETE_REQUESTED'));
        $this->assertSame('warehouse.catalog.mutation.completed', config('rabbit-transport.outbound.WAREHOUSE_CATALOG_MUTATION_COMPLETED'));

        $bindings = (array) config('rabbit-transport.setup.bindings');

        $this->assertContains('crm.warehouse.brands.create', $bindings);
        $this->assertContains('crm.warehouse.brands.update', $bindings);
        $this->assertContains('crm.warehouse.brands.delete', $bindings);
        $this->assertContains('crm.warehouse.nomenclatures.create', $bindings);
        $this->assertContains('crm.warehouse.nomenclatures.update', $bindings);
        $this->assertContains('crm.warehouse.nomenclatures.delete', $bindings);
        $this->assertContains('crm.warehouse.pack-dimensions.create', $bindings);
        $this->assertContains('crm.warehouse.pack-dimensions.update', $bindings);
        $this->assertContains('crm.warehouse.pack-dimensions.delete', $bindings);
        $this->assertContains('crm.warehouse.kits.create', $bindings);
        $this->assertContains('crm.warehouse.kits.update', $bindings);
        $this->assertContains('crm.warehouse.kits.delete', $bindings);
    }

    /**
     * @return array{0: Type, 1: Brand, 2: PackDimension}
     */
    private function createBaseCatalog(): array
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create($this->brandAttributes());
        $packDimension = PackDimension::query()->create($this->packDimensionAttributes($type->id));

        return [$type, $brand, $packDimension];
    }

    /**
     * @param  array<int, int>  $nomenclatureIds
     * @return array<string, mixed>
     */
    private function payload(
        string $operationId,
        string $operation,
        array $nomenclatureIds,
        ?int $id = null,
    ): array {
        $kit = [
            'nomenclature_ids' => $nomenclatureIds,
            'is_sale_separately' => true,
            'is_active' => true,
            'guarantee' => 12,
        ];

        if ($id !== null) {
            $kit['id'] = $id;
        }

        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'kit' => $kit,
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
            'material' => [],
            'vehicle_type' => [],
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
    private function kitAttributes(int $typeId, int $packDimensionId, string $importHash): array
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
            'import_hash' => $importHash,
            'is_sale_separately' => false,
            'is_active' => true,
        ];
    }
}
