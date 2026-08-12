<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Read\DTO\KitCrmResource as WireKitCrmResource;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Read\DTO\PaginationMeta as WirePaginationMeta;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Catalog\Read\DTO\WarehouseCrmOption as WireWarehouseCrmOption;
use Tests\TestCase;

/**
 * Проверяет REST read API Warehouse-комплектов для CRM/Filament.
 */
final class KitCrmReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_filtered_sorted_paginated_kits(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $packDimension = $this->createPackDimension($type, name: 'Box 600');
        $otherPackDimension = $this->createPackDimension($type, name: 'Box 450');

        $this->createKit($type, $packDimension, complectation: 'Front pair', weight: 900);
        $this->createKit($type, $packDimension, complectation: 'Rear pair', weight: 700);
        $this->createKit($type, $otherPackDimension, complectation: 'Other box', weight: 300);

        $response = $this->getJson("/api/v1/crm/warehouse/kits?per_page=10&filter[pack_dimension_id][]={$packDimension->id}&sort=-weight");

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.complectation', 'Front pair')
            ->assertJsonPath('data.1.complectation', 'Rear pair')
            ->assertJsonPath('data.0.type_name', 'Дворники')
            ->assertJsonPath('data.0.pack_dimension_name', 'Box 600');

        self::assertSame($response->json('data.0'), WireKitCrmResource::fromArray($response->json('data.0'))->toArray());
        self::assertSame($response->json('meta'), WirePaginationMeta::fromArray($response->json('meta'))->toArray());
    }

    public function test_show_returns_kit_details_with_nomenclatures(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $brand = $this->createBrand(name: 'Denso');
        $packDimension = $this->createPackDimension($type, name: 'Box 600');
        $kit = $this->createKit($type, $packDimension, complectation: 'Front pair');
        $nomenclature = $this->createNomenclature($type, $brand, partNumber: 'DUR-060L', name: 'Denso Hybrid');

        DB::table('kit_nomenclature')->insert([
            'kit_id' => $kit->id,
            'nomenclature_id' => $nomenclature->id,
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/crm/warehouse/kits/{$kit->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $kit->id)
            ->assertJsonPath('data.complectation', 'Front pair')
            ->assertJsonPath('data.nomenclatures_count', 1)
            ->assertJsonPath('data.nomenclatures.0.part_number', 'DUR-060L')
            ->assertJsonPath('data.nomenclatures.0.label', '[DUR-060L] Denso Hybrid');

        self::assertSame($response->json('data'), WireKitCrmResource::fromArray($response->json('data'))->toArray());
    }

    public function test_show_returns_not_found_for_missing_kit(): void
    {
        $response = $this->getJson('/api/v1/crm/warehouse/kits/999999');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Kit not found.');
    }

    public function test_option_endpoints_return_nomenclatures_pack_dimensions_and_types(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $brand = $this->createBrand(name: 'Denso');
        $packDimension = $this->createPackDimension($type, name: 'Box 600');
        $this->createNomenclature($type, $brand, partNumber: 'DUR-060L', name: 'Denso Hybrid');

        $nomenclatureResponse = $this->getJson('/api/v1/crm/warehouse/kits/options/nomenclatures?q=Hybrid');
        $nomenclatureResponse
            ->assertOk()
            ->assertJsonPath('data.0.label', '[DUR-060L] Denso Hybrid')
            ->assertJsonPath('data.0.part_number', 'DUR-060L')
            ->assertJsonPath('data.0.brand_name', 'Denso');

        self::assertSame($nomenclatureResponse->json('data.0'), WireWarehouseCrmOption::fromArray($nomenclatureResponse->json('data.0'))->toArray());

        $packDimensionResponse = $this->getJson('/api/v1/crm/warehouse/kits/options/pack-dimensions?q=Box');
        $packDimensionResponse
            ->assertOk()
            ->assertJsonPath('data.0.id', $packDimension->id)
            ->assertJsonPath('data.0.label', 'Box 600')
            ->assertJsonPath('data.0.type_name', 'Дворники');

        self::assertSame($packDimensionResponse->json('data.0'), WireWarehouseCrmOption::fromArray($packDimensionResponse->json('data.0'))->toArray());

        $typeResponse = $this->getJson('/api/v1/crm/warehouse/kits/options/types?q=Дворники');
        $typeResponse
            ->assertOk()
            ->assertJsonPath('data.0.id', $type->id)
            ->assertJsonPath('data.0.char', 'WB');

        self::assertSame($typeResponse->json('data.0'), WireWarehouseCrmOption::fromArray($typeResponse->json('data.0'))->toArray());
    }

    private function createType(string $name, string $char): Type
    {
        return Type::query()->create([
            'name' => $name,
            'char' => $char,
        ]);
    }

    private function createBrand(string $name, string $char = 'D'): Brand
    {
        return Brand::query()->create([
            'name' => $name,
            'number_sert' => 'CERT-1',
            'date_start' => now()->subDay(),
            'date_end' => now()->addYear(),
            'char' => $char,
        ]);
    }

    private function createPackDimension(Type $type, string $name): PackDimension
    {
        return PackDimension::query()->create([
            'type_id' => $type->id,
            'name' => $name,
            'weight' => 120,
            'width' => 10,
            'height' => 20,
            'length' => 60,
            'price' => 300,
            'generated' => false,
        ]);
    }

    private function createKit(Type $type, PackDimension $packDimension, string $complectation, int $weight = 900): Kit
    {
        return Kit::query()->create([
            'type_id' => $type->id,
            'pack_dimension_id' => $packDimension->id,
            'complectation' => $complectation,
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => $weight,
            'import_hash' => null,
            'is_sale_separately' => true,
            'is_active' => true,
        ]);
    }

    private function createNomenclature(Type $type, Brand $brand, string $partNumber, string $name): Nomenclature
    {
        return Nomenclature::query()->create([
            'type_id' => $type->id,
            'brand_id' => $brand->id,
            'name' => $name,
            'country' => 'Япония',
            'part_number' => $partNumber,
            'color' => 'black',
            'weight' => 400,
            'material' => ['Резина'],
            'vehicle_type' => ['Легковые автомобили'],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => ['category' => 'Бескаркасная'],
        ]);
    }
}
