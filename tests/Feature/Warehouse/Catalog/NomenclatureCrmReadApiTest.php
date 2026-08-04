<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmReadRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Проверяет REST read API Warehouse-номенклатуры для CRM/Filament.
 */
final class NomenclatureCrmReadApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что read API закрывается service key, когда он задан в конфиге.
     */
    public function test_read_api_requires_service_key_when_configured(): void
    {
        config(['services.dan_vehicles.read_api_key' => 'secret-key']);

        $response = $this->getJson('/api/v1/warehouse/nomenclatures');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');
    }

    /**
     * Проверяет список номенклатуры: фильтры, сортировку и meta пагинации.
     */
    public function test_index_returns_filtered_sorted_paginated_nomenclatures(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $brand = $this->createBrand(name: 'Denso');
        $otherBrand = $this->createBrand(name: 'Bosch');

        $this->createNomenclature(type: $type, brand: $brand, name: 'Denso Hybrid', partNumber: 'DUR-060L');
        $this->createNomenclature(type: $type, brand: $brand, name: 'Denso Flat', partNumber: 'DUR-050L');
        $this->createNomenclature(type: $type, brand: $otherBrand, name: 'Bosch Aero', partNumber: 'AR-600');

        $response = $this->getJson("/api/v1/warehouse/nomenclatures?per_page=10&filter[brand_id][]={$brand->id}&sort=-name");

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Denso Hybrid')
            ->assertJsonPath('data.1.name', 'Denso Flat')
            ->assertJsonPath('data.0.type_name', 'Дворники')
            ->assertJsonPath('data.0.brand_name', 'Denso')
            ->assertJsonPath('data.0.type_template', 'wiper');
    }

    /**
     * Проверяет detail endpoint с details/material/vehicle_type.
     */
    public function test_show_returns_nomenclature_details(): void
    {
        $nomenclature = $this->createNomenclature(
            type: $this->createType(name: 'Дворники', char: 'WB'),
            brand: $this->createBrand(name: 'Denso'),
            name: 'Denso Hybrid',
            partNumber: 'DUR-060L',
        );

        $response = $this->getJson("/api/v1/warehouse/nomenclatures/{$nomenclature->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $nomenclature->id)
            ->assertJsonPath('data.part_number', 'DUR-060L')
            ->assertJsonPath('data.material.0', 'Резина')
            ->assertJsonPath('data.vehicle_type.0', 'Легковые автомобили')
            ->assertJsonPath('data.details.category', 'Бескаркасная');
    }

    /**
     * Проверяет 404 для отсутствующей номенклатуры.
     */
    public function test_show_returns_not_found_for_missing_nomenclature(): void
    {
        $response = $this->getJson('/api/v1/warehouse/nomenclatures/999999');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Nomenclature not found.');
    }

    /**
     * Проверяет search endpoint и ограничение limit.
     */
    public function test_search_returns_limited_nomenclature_options(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $brand = $this->createBrand(name: 'Denso');

        $this->createNomenclature(type: $type, brand: $brand, name: 'Denso Hybrid', partNumber: 'DUR-060L');
        $this->createNomenclature(type: $type, brand: $brand, name: 'Denso Flat', partNumber: 'DUR-050L');
        $this->createNomenclature(type: $type, brand: $brand, name: 'Bosch Aero', partNumber: 'AR-600');

        $response = $this->getJson('/api/v1/warehouse/nomenclatures/search?q=Hybrid&limit=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', "{$this->latestNomenclatureId()} | DUR-060L | Denso | Denso Hybrid")
            ->assertJsonPath('data.0.part_number', 'DUR-060L');
    }

    /**
     * Проверяет option endpoints для CRM-формы.
     */
    public function test_option_endpoints_return_types_and_brands(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $brand = $this->createBrand(name: 'Denso', char: 'D');

        $this->getJson('/api/v1/warehouse/nomenclatures/options/types?q=Дворники')
            ->assertOk()
            ->assertJsonPath('data.0.id', $type->id)
            ->assertJsonPath('data.0.char', 'WB')
            ->assertJsonPath('data.0.template', 'wiper');

        $this->getJson('/api/v1/warehouse/nomenclatures/options/brands?q=Denso')
            ->assertOk()
            ->assertJsonPath('data.0.id', $brand->id)
            ->assertJsonPath('data.0.char', 'D')
            ->assertJsonPath('data.0.label', 'Denso');
    }

    /**
     * Проверяет, что option endpoints подходят для preload в Filament, а не режутся первыми 50.
     */
    public function test_option_endpoints_can_preload_more_than_default_search_limit(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->createType(name: sprintf('Type %02d', $i), char: 'VB');
            $this->createBrand(name: sprintf('Brand %02d', $i), char: 'B');
        }

        $this->getJson('/api/v1/warehouse/nomenclatures/options/types?limit=60')
            ->assertOk()
            ->assertJsonCount(60, 'data');

        $this->getJson('/api/v1/warehouse/nomenclatures/options/brands?limit=60')
            ->assertOk()
            ->assertJsonCount(60, 'data');
    }

    /**
     * Проверяет, что CRM read repository отдаёт локальные DTO, а не raw arrays.
     */
    public function test_repository_returns_local_crm_read_dtos(): void
    {
        $nomenclature = $this->createNomenclature(
            type: $this->createType(name: 'Дворники', char: 'WB'),
            brand: $this->createBrand(name: 'Denso'),
            name: 'Denso Hybrid',
            partNumber: 'DUR-060L',
        );

        $repository = app(NomenclatureCrmReadRepositoryInterface::class);
        $query = new NomenclatureCrmReadQueryDTO(perPage: 10);
        $page = $repository->paginate($query);
        $detail = $repository->find((int) $nomenclature->id);
        $search = $repository->search('Denso');
        $types = $repository->typeOptions('Дворники');

        self::assertInstanceOf(NomenclatureCrmPageDTO::class, $page);
        self::assertInstanceOf(NomenclatureCrmListItemDTO::class, $detail);
        self::assertInstanceOf(NomenclatureCrmSearchItemDTO::class, $search->first());
        self::assertInstanceOf(NomenclatureCrmOptionDTO::class, $types->first());
    }

    /**
     * Создаёт тип номенклатуры.
     */
    private function createType(string $name, string $char): Type
    {
        return Type::query()->create([
            'name' => $name,
            'char' => $char,
        ]);
    }

    /**
     * Создаёт бренд номенклатуры.
     */
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

    /**
     * Создаёт номенклатуру для read API тестов.
     */
    private function createNomenclature(Type $type, Brand $brand, string $name, string $partNumber): Nomenclature
    {
        return Nomenclature::query()->create([
            'type_id' => $type->id,
            'brand_id' => $brand->id,
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

    private function latestNomenclatureId(): int
    {
        return (int) Nomenclature::query()->where('part_number', 'DUR-060L')->value('id');
    }
}
