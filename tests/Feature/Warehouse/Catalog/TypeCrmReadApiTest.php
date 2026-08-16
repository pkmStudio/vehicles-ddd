<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\TypeCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Проверяет REST read API Warehouse-типов для CRM/Filament.
 */
final class TypeCrmReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_api_requires_service_key_when_configured(): void
    {
        config(['services.dan_vehicles.read_api_key' => 'secret-key']);

        $this->getJson('/api/v1/crm/warehouse/types')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withHeader('X-Service-Key', 'secret-key')
            ->getJson('/api/v1/crm/warehouse/types')
            ->assertOk();
    }

    public function test_index_returns_filtered_sorted_paginated_types(): void
    {
        $wiper = $this->createType(name: 'Дворники', char: 'WB');
        $sparkPlug = $this->createType(name: 'Свечи зажигания', char: 'SP');
        $this->createType(name: 'Колодки', char: 'BP');
        $this->createNomenclature($wiper);
        $this->createNomenclature($wiper);
        $this->createNomenclature($sparkPlug);

        $response = $this->getJson('/api/v1/crm/warehouse/types?per_page=10&search=двор&sort=-name&filter[char]=WB');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $wiper->id)
            ->assertJsonPath('data.0.name', 'Дворники')
            ->assertJsonPath('data.0.char', 'WB')
            ->assertJsonPath('data.0.nomenclatures_count', 2);
    }

    public function test_show_returns_type_details(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $this->createNomenclature($type);

        $response = $this->getJson("/api/v1/crm/warehouse/types/{$type->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $type->id)
            ->assertJsonPath('data.name', 'Дворники')
            ->assertJsonPath('data.char', 'WB')
            ->assertJsonPath('data.nomenclatures_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'char',
                    'nomenclatures_count',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_show_returns_not_found_for_missing_type(): void
    {
        $this->getJson('/api/v1/crm/warehouse/types/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Type not found.');
    }

    public function test_repository_returns_local_crm_read_dtos(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');

        $repository = app(TypeCrmRepositoryInterface::class);
        $query = new TypeCrmReadQueryDTO(perPage: 10);
        $page = $repository->paginate($query);
        $detail = $repository->findById((int) $type->id);

        self::assertInstanceOf(TypeCrmPageDTO::class, $page);
        self::assertInstanceOf(TypeCrmListItemDTO::class, $page->data->first());
        self::assertInstanceOf(TypeCrmListItemDTO::class, $detail);
    }

    private function createType(string $name, string $char): Type
    {
        return Type::query()->create([
            'name' => $name,
            'char' => $char,
        ]);
    }

    private function createNomenclature(Type $type): Nomenclature
    {
        $brand = Brand::query()->create([
            'name' => 'Denso',
            'number_sert' => 'CERT-D',
            'date_start' => now()->subDay(),
            'date_end' => now()->addYear(),
            'char' => 'D',
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
