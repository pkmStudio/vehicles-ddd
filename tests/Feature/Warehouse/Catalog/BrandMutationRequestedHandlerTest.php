<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers\BrandMutationRequestedHandler;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\BrandMutationPayload as WireBrandMutationPayload;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Mutation\DTO\BrandMutationRequested as WireBrandMutationRequested;
use PkmStudio\DanWireContracts\Vehicles\Shared\Enums\CatalogMutationOperation as WireCatalogMutationOperation;
use Tests\TestCase;

/**
 * Покрывает RabbitMQ CRUD-сценарий Warehouse/Catalog для брендов.
 */
final class BrandMutationRequestedHandlerTest extends TestCase
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

    public function test_brand_create_update_and_delete_messages(): void
    {
        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->times(3)
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Brand
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed));

        app(BrandMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-brand-create-1',
            operation: 'create',
            name: 'Bosch',
        ));

        $brand = Brand::query()->where('name', 'Bosch')->firstOrFail();

        app(BrandMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-brand-update-1',
            operation: 'update',
            name: 'Bosch Updated',
            id: $brand->id,
        ));

        app(BrandMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'warehouse-brand-delete-1',
            'operation' => 'delete',
            'brand' => [
                'id' => $brand->id,
            ],
        ]);

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_brand_create_message_accepts_published_wire_contract_payload(): void
    {
        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Brand
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed
                && $result->operationId === 'warehouse-brand-wire-contract'
                && $result->recordId !== null));

        $message = new WireBrandMutationRequested(
            userId: 42,
            operationId: 'warehouse-brand-wire-contract',
            operation: WireCatalogMutationOperation::Create->value,
            brand: new WireBrandMutationPayload(
                name: 'Wire Bosch',
                numberSert: 'WIRE-CERT-1',
                dateStart: '2026-01-01 00:00:00',
                dateEnd: '2026-12-31 00:00:00',
                char: 'W',
            ),
        );

        app(BrandMutationRequestedHandler::class)->handle($message->toArray());

        $this->assertDatabaseHas('brands', [
            'name' => 'Wire Bosch',
            'number_sert' => 'WIRE-CERT-1',
            'char' => 'W',
        ]);
    }

    public function test_brand_create_is_rejected_when_name_already_exists(): void
    {
        Brand::query()->create($this->brandAttributes('Bosch'));

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Brand
                && $result->operation === WarehouseCatalogMutationOperationEnum::Create
                && $result->status === WarehouseCatalogMutationStatusEnum::Rejected
                && $result->reason === WarehouseCatalogMutationRejectReasonEnum::AlreadyExists->value));

        app(BrandMutationRequestedHandler::class)->handle($this->payload(
            operationId: 'warehouse-brand-duplicate-1',
            operation: 'create',
            name: 'Bosch',
        ));

        $this->assertSame(1, Brand::query()->where('name', 'Bosch')->count());
    }

    public function test_brand_delete_cascades_related_nomenclatures(): void
    {
        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create($this->brandAttributes('Bosch'));
        Nomenclature::query()->create($this->nomenclatureAttributes(
            typeId: $type->id,
            brandId: $brand->id,
            partNumber: 'VB-1',
        ));

        $notifier = $this->mock(WarehouseCatalogMutationNotificationServiceInterface::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (WarehouseCatalogMutationResultDTO $result): bool => $result->entity === WarehouseCatalogEntityEnum::Brand
                && $result->operation === WarehouseCatalogMutationOperationEnum::Delete
                && $result->status === WarehouseCatalogMutationStatusEnum::Completed));

        app(BrandMutationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'warehouse-brand-delete-cascade-1',
            'operation' => 'delete',
            'brand' => [
                'id' => $brand->id,
            ],
        ]);

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        $this->assertDatabaseMissing('nomenclatures', ['brand_id' => $brand->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $operationId, string $operation, string $name, ?int $id = null): array
    {
        $brand = $this->brandAttributes($name);

        if ($id !== null) {
            $brand['id'] = $id;
        }

        return [
            'user_id' => 42,
            'operation_id' => $operationId,
            'operation' => $operation,
            'brand' => $brand,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function brandAttributes(string $name): array
    {
        return [
            'name' => $name,
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
}
