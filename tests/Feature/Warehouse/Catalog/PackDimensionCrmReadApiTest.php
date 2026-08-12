<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Проверяет REST read API Warehouse-упаковочных размеров для CRM/Filament.
 */
final class PackDimensionCrmReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_filtered_sorted_paginated_pack_dimensions(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $otherType = $this->createType(name: 'Свечи', char: 'SP');

        $this->createPackDimension($type, name: 'Box 600', weight: 120);
        $this->createPackDimension($type, name: 'Box 450', weight: 90);
        $this->createPackDimension($otherType, name: 'Spark box', weight: 20);

        $response = $this->getJson("/api/v1/crm/warehouse/pack-dimensions?per_page=10&filter[type_id][]={$type->id}&sort=-weight");

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Box 600')
            ->assertJsonPath('data.1.name', 'Box 450')
            ->assertJsonPath('data.0.type_name', 'Дворники');
    }

    public function test_show_returns_pack_dimension_details_with_kits_count(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $packDimension = $this->createPackDimension($type, name: 'Box 600', weight: 120);
        $this->createKit($type, $packDimension);

        $response = $this->getJson("/api/v1/crm/warehouse/pack-dimensions/{$packDimension->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $packDimension->id)
            ->assertJsonPath('data.name', 'Box 600')
            ->assertJsonPath('data.type_char', 'WB')
            ->assertJsonPath('data.kits_count', 1);
    }

    public function test_show_returns_not_found_for_missing_pack_dimension(): void
    {
        $response = $this->getJson('/api/v1/crm/warehouse/pack-dimensions/999999');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Pack dimension not found.');
    }

    public function test_type_options_return_matching_types(): void
    {
        $type = $this->createType(name: 'Дворники', char: 'WB');
        $this->createType(name: 'Свечи', char: 'SP');

        $this->getJson('/api/v1/crm/warehouse/pack-dimensions/options/types?q=Дворники')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $type->id)
            ->assertJsonPath('data.0.char', 'WB');
    }

    private function createType(string $name, string $char): Type
    {
        return Type::query()->create([
            'name' => $name,
            'char' => $char,
        ]);
    }

    private function createPackDimension(Type $type, string $name, int $weight): PackDimension
    {
        return PackDimension::query()->create([
            'type_id' => $type->id,
            'name' => $name,
            'weight' => $weight,
            'width' => 10,
            'height' => 20,
            'length' => 60,
            'price' => 300,
            'generated' => false,
        ]);
    }

    private function createKit(Type $type, PackDimension $packDimension): Kit
    {
        return Kit::query()->create([
            'type_id' => $type->id,
            'pack_dimension_id' => $packDimension->id,
            'complectation' => 'Front pair',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 900,
            'import_hash' => null,
            'is_sale_separately' => true,
            'is_active' => true,
        ]);
    }
}
