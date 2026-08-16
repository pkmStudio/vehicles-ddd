<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Проверяет REST API Warehouse-номенклатуры для dan-catalog.
 */
final class NomenclatureCatalogRestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_nomenclature_api_requires_service_key(): void
    {
        config(['services.dan_catalog.read_api_key' => 'catalog-secret']);

        $this->getJson('/api/v1/catalog/categories')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withHeader('X-Service-Key', 'catalog-secret')
            ->getJson('/api/v1/catalog/categories')
            ->assertOk();
    }

    public function test_categories_are_flat_include_counts_and_omit_empty_categories(): void
    {
        $wipers = $this->createType('Дворники', 'WB');
        $this->createType('Фильтры', 'OF');
        $brand = $this->createBrand('Denso');
        $wipersId = (int) $wipers->getKey();

        $this->createNomenclature($wipers, $brand, 'Denso Hybrid', 'DUR-060L');
        $this->createNomenclature($wipers, $brand, 'Denso Flat', 'DUR-050L');

        $this->getJson('/api/v1/catalog/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $wipersId)
            ->assertJsonPath('data.0.code', 'WB')
            ->assertJsonPath('data.0.nomenclature_count', 2)
            ->assertJsonMissing(['name' => 'Фильтры']);
    }

    public function test_catalog_defaults_to_dan_brand_and_accepts_explicit_brand_filter(): void
    {
        $type = $this->createType('Дворники', 'WB');
        $typeId = (int) $type->getKey();
        $dan = $this->createBrand('DAN');
        $denso = $this->createBrand('Denso', 'D', 4);

        $this->createNomenclature($type, $dan, 'DAN Flat', 'DAN-050L');
        $this->createNomenclature($type, $denso, 'Denso Hybrid', 'DUR-060L');

        $this->getJson('/api/v1/catalog/categories')
            ->assertOk()
            ->assertJsonPath('data.0.nomenclature_count', 1);

        $this->getJson("/api/v1/catalog/categories/{$typeId}/nomenclatures")
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.part_number', 'DAN-050L');

        $this->getJson('/api/v1/catalog/search?q=Denso')
            ->assertOk()
            ->assertJsonPath('data.match', 'empty');

        $this->getJson('/api/v1/catalog/nomenclatures/DUR-060L')
            ->assertNotFound();

        $this->getJson('/api/v1/catalog/search?q=Denso&brand_id=4')
            ->assertOk()
            ->assertJsonPath('data.items.0.part_number', 'DUR-060L');

        $this->getJson('/api/v1/catalog/nomenclatures/DUR-060L?brand_id=4')
            ->assertOk()
            ->assertJsonPath('data.brand_id', 4);

        $this->getJson('/api/v1/catalog/categories?brand_id=0')
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid brand parameter.');

        $this->getJson('/api/v1/catalog/categories?brand_id=3abc')
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid brand parameter.');
    }

    public function test_category_nomenclatures_are_paginated(): void
    {
        $wipers = $this->createType('Дворники', 'WB');
        $filters = $this->createType('Фильтры', 'OF');
        $brand = $this->createBrand('Denso');
        $wipersId = (int) $wipers->getKey();

        $this->createNomenclature($wipers, $brand, 'Denso Flat', 'DUR-050L');
        $this->createNomenclature($wipers, $brand, 'Denso Hybrid', 'DUR-060L');
        $this->createNomenclature($filters, $brand, 'Denso Oil', 'DOF-1');

        $this->getJson("/api/v1/catalog/categories/{$wipersId}/nomenclatures?page=2&page_size=1")
            ->assertOk()
            ->assertJsonPath('data.category.id', $wipersId)
            ->assertJsonPath('data.items.0.part_number', 'DUR-060L')
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.page', 2)
            ->assertJsonPath('data.page_size', 1)
            ->assertJsonPath('data.page_count', 2);

        $this->getJson('/api/v1/catalog/categories/999999/nomenclatures')
            ->assertNotFound()
            ->assertJsonPath('message', 'Category not found.');

        $this->getJson("/api/v1/catalog/categories/{$wipersId}/nomenclatures?page=0")
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid pagination parameters.');
    }

    public function test_nomenclature_detail_uses_case_insensitive_part_number(): void
    {
        $type = $this->createType('Дворники', 'WB');
        $brand = $this->createBrand('Denso', 'D');
        $typeId = (int) $type->getKey();
        $this->createNomenclature($type, $brand, 'Denso Hybrid', 'DUR-060L');
        $this->createNomenclature($type, $brand, 'Denso Slash', 'DUR/050L');

        $this->getJson('/api/v1/catalog/nomenclatures/dur-060l')
            ->assertOk()
            ->assertJsonPath('data.part_number', 'DUR-060L')
            ->assertJsonPath('data.category_id', $typeId)
            ->assertJsonPath('data.category_code', 'WB')
            ->assertJsonPath('data.brand_name', 'Denso')
            ->assertJsonPath('data.material.0', 'Резина')
            ->assertJsonPath('data.details.length_main', 600);

        $this->getJson('/api/v1/catalog/nomenclatures/DUR%2F050L')
            ->assertOk()
            ->assertJsonPath('data.part_number', 'DUR/050L');

        $this->getJson('/api/v1/catalog/nomenclatures/UNKNOWN')
            ->assertNotFound()
            ->assertJsonPath('message', 'Nomenclature not found.');
    }

    public function test_search_uses_only_part_number_and_name_and_limits_results(): void
    {
        $type = $this->createType('Дворники', 'WB');
        $brand = $this->createBrand('Denso');
        $this->createNomenclature($type, $brand, 'Denso Hybrid', 'DUR-060L');
        $this->createNomenclature($type, $brand, 'Denso Flat', 'DUR-050L');

        $this->getJson('/api/v1/catalog/search?q=dur-060l')
            ->assertOk()
            ->assertJsonPath('data.match', 'exact')
            ->assertJsonPath('data.items.0.part_number', 'DUR-060L');

        $this->getJson('/api/v1/catalog/search?q=Denso&limit=1')
            ->assertOk()
            ->assertJsonPath('data.match', 'multiple')
            ->assertJsonCount(1, 'data.items');

        $this->getJson('/api/v1/catalog/search?q=OEM-IGNORED')
            ->assertOk()
            ->assertJsonPath('data.match', 'empty')
            ->assertJsonCount(0, 'data.items');

        $this->getJson('/api/v1/catalog/search?q=')
            ->assertBadRequest()
            ->assertJsonPath('message', 'Invalid search parameters.');
    }

    private function createType(string $name, ?string $char): Type
    {
        return Type::query()->create([
            'name' => $name,
            'char' => $char,
        ]);
    }

    private function createBrand(string $name, string $char = 'D', int $id = 3): Brand
    {
        return Brand::query()->create([
            'id' => $id,
            'name' => $name,
            'number_sert' => 'CERT-1',
            'date_start' => now()->subDay(),
            'date_end' => now()->addYear(),
            'char' => $char,
        ]);
    }

    private function createNomenclature(Type $type, Brand $brand, string $name, string $partNumber): Nomenclature
    {
        return Nomenclature::query()->create([
            'type_id' => (int) $type->getKey(),
            'brand_id' => (int) $brand->getKey(),
            'name' => $name,
            'country' => 'Япония',
            'part_number' => $partNumber,
            'color' => 'Черный',
            'weight' => 320,
            'material' => ['Резина'],
            'vehicle_type' => ['Легковые автомобили'],
            'quantity_pak' => 1,
            'quantity_in_pak' => 2,
            'details' => [
                'category' => 'Бескаркасная',
                'length_main' => 600,
            ],
        ]);
    }
}
