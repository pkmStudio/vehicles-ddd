<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\MoySklad;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Services\NomenclatureSyncServiceInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductPayloadDTO;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\NomenclatureIntegration;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Покрывает state machine синхронизации Warehouse-номенклатуры с МойСклад.
 */
final class NomenclatureSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Включает feature flag MoySklad и выключает реальные productFolder-запросы.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'warehouse.moysklad.nomenclature_sync.enabled' => true,
            'warehouse.moysklad.nomenclature_sync.product_folders.enabled' => false,
        ]);
    }

    /**
     * Проверяет create-сценарий и запись успешного integration-state.
     */
    public function test_sync_creates_product_and_marks_integration_synced(): void
    {
        $nomenclature = $this->createNomenclature('BP-1');

        $client = $this->mock(MoySkladProductClientInterface::class);
        $client->shouldReceive('findByArticle')->once()->with('BP-1')->andReturn(null);
        $client->shouldReceive('findByExternalCode')->once()->with("nomenclature:{$nomenclature->id}")->andReturn(null);
        $client->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (MoySkladProductPayloadDTO $payload): bool => $payload->article === 'BP-1'
                && $payload->externalCode === "nomenclature:{$nomenclature->id}"))
            ->andReturn(new MoySkladProductDTO(
                id: '11111111-1111-1111-1111-111111111111',
                externalCode: "nomenclature:{$nomenclature->id}",
            ));

        app(NomenclatureSyncServiceInterface::class)->sync($nomenclature->id);

        $this->assertDatabaseHas('nomenclature_integrations', [
            'nomenclature_id' => $nomenclature->id,
            'provider' => 'moysklad',
            'external_id' => '11111111-1111-1111-1111-111111111111',
            'external_code' => "nomenclature:{$nomenclature->id}",
            'sync_status' => 'synced',
            'last_error' => null,
        ]);
    }

    /**
     * Проверяет удаление товара МойСклад и статус `deleted` у integration.
     */
    public function test_delete_removes_product_and_marks_integration_deleted(): void
    {
        $nomenclature = $this->createNomenclature('BP-2');

        $integration = NomenclatureIntegration::query()->create([
            'nomenclature_id' => $nomenclature->id,
            'provider' => 'moysklad',
            'external_id' => '22222222-2222-2222-2222-222222222222',
            'external_code' => "nomenclature:{$nomenclature->id}",
            'sync_status' => 'synced',
        ]);

        $client = $this->mock(MoySkladProductClientInterface::class);
        $client->shouldReceive('deleteById')
            ->once()
            ->with('22222222-2222-2222-2222-222222222222');

        app(NomenclatureSyncServiceInterface::class)->delete(
            nomenclatureId: $nomenclature->id,
            partNumber: 'BP-2',
            integrationId: $integration->id,
        );

        $this->assertDatabaseHas('nomenclature_integrations', [
            'id' => $integration->id,
            'sync_status' => 'deleted',
            'last_error' => null,
        ]);
    }

    /**
     * Создаёт минимальную Warehouse-номенклатуру с типом и брендом.
     */
    private function createNomenclature(string $partNumber): Nomenclature
    {
        $type = Type::query()->create(['name' => 'Brake Pads', 'char' => 'BP']);
        $brand = Brand::query()->create([
            'name' => 'Bosch',
            'number_sert' => 'CERT-1',
            'date_start' => now(),
            'date_end' => now(),
            'char' => 'B',
        ]);

        return Nomenclature::query()->create([
            'type_id' => $type->id,
            'brand_id' => $brand->id,
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
        ]);
    }
}
