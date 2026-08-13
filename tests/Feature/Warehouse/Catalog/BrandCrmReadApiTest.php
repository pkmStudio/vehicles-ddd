<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Проверяет REST read API Warehouse-брендов для CRM/Filament.
 */
final class BrandCrmReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_api_requires_service_key_when_configured(): void
    {
        config(['services.dan_vehicles.read_api_key' => 'secret-key']);

        $this->getJson('/api/v1/crm/warehouse/brands')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withHeader('X-Service-Key', 'secret-key')
            ->getJson('/api/v1/crm/warehouse/brands')
            ->assertOk();
    }

    public function test_index_returns_filtered_sorted_paginated_brands(): void
    {
        $denso = $this->createBrand(name: 'Denso', char: 'D', numberSert: 'CERT-D');
        $bosch = $this->createBrand(name: 'Bosch', char: 'B', numberSert: 'CERT-B');
        $this->createBrand(name: 'Avantech', char: 'A', numberSert: 'CERT-A');
        $this->createNomenclature($denso);
        $this->createNomenclature($denso);
        $this->createNomenclature($bosch);

        $response = $this->getJson('/api/v1/crm/warehouse/brands?per_page=10&search=cert&sort=-name&filter[char]=D');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $denso->id)
            ->assertJsonPath('data.0.name', 'Denso')
            ->assertJsonPath('data.0.number_sert', 'CERT-D')
            ->assertJsonPath('data.0.char', 'D')
            ->assertJsonPath('data.0.nomenclatures_count', 2);
    }

    public function test_show_returns_brand_details(): void
    {
        $brand = $this->createBrand(name: 'Denso', char: 'D', numberSert: 'CERT-D');
        $this->createNomenclature($brand);

        $response = $this->getJson("/api/v1/crm/warehouse/brands/{$brand->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $brand->id)
            ->assertJsonPath('data.name', 'Denso')
            ->assertJsonPath('data.number_sert', 'CERT-D')
            ->assertJsonPath('data.nomenclatures_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'number_sert',
                    'date_start',
                    'date_end',
                    'char',
                    'nomenclatures_count',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_show_returns_not_found_for_missing_brand(): void
    {
        $this->getJson('/api/v1/crm/warehouse/brands/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Brand not found.');
    }

    public function test_repository_returns_local_crm_read_dtos(): void
    {
        $brand = $this->createBrand(name: 'Denso', char: 'D', numberSert: 'CERT-D');

        $repository = app(BrandCrmRepositoryInterface::class);
        $query = new BrandCrmReadQueryDTO(perPage: 10);
        $page = $repository->paginate($query);
        $detail = $repository->findById((int) $brand->id);

        self::assertInstanceOf(BrandCrmPageDTO::class, $page);
        self::assertInstanceOf(BrandCrmListItemDTO::class, $page->data->first());
        self::assertInstanceOf(BrandCrmListItemDTO::class, $detail);
    }

    private function createBrand(string $name, string $char, string $numberSert): Brand
    {
        return Brand::query()->create([
            'name' => $name,
            'number_sert' => $numberSert,
            'date_start' => now()->subDay(),
            'date_end' => now()->addYear(),
            'char' => $char,
        ]);
    }

    private function createNomenclature(Brand $brand): Nomenclature
    {
        $type = Type::query()->create([
            'name' => 'Дворники',
            'char' => 'WB',
        ]);

        return Nomenclature::query()->create([
            'type_id' => $type->id,
            'brand_id' => $brand->id,
            'name' => 'Denso Hybrid',
            'country' => 'Япония',
            'part_number' => 'DUR-'.fake()->unique()->numerify('###'),
            'color' => 'Черный',
            'weight' => 320,
            'material' => ['Резина'],
            'vehicle_type' => ['Легковые автомобили'],
            'quantity_pak' => 1,
            'quantity_in_pak' => 2,
            'details' => ['category' => 'Бескаркасная'],
        ]);
    }
}
