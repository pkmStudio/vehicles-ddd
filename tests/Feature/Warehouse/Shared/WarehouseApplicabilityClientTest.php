<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Shared;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class WarehouseApplicabilityClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_kits_expose_owner_resolved_type_templates(): void
    {
        $kitTypeId = $this->createType(id: 4, name: 'ФИЛЬТР МАСЛЯНЫЙ', char: 'WB');
        $idFallbackTypeId = $this->createType(id: 2, name: 'Неизвестный тип', char: 'ZZ');
        $nameFallbackTypeId = $this->createType(id: 999, name: ' свечи зажигания ');
        $brandId = $this->createBrand();
        $packDimensionId = $this->createPackDimension($kitTypeId);
        $kitId = $this->createKit(typeId: $kitTypeId, packDimensionId: $packDimensionId);
        $firstNomenclatureId = $this->createNomenclature(typeId: $idFallbackTypeId, brandId: $brandId, partNumber: 'SP-1');
        $secondNomenclatureId = $this->createNomenclature(typeId: $nameFallbackTypeId, brandId: $brandId, partNumber: 'SP-2');

        DB::table('kit_nomenclature')->insert([
            [
                'kit_id' => $kitId,
                'nomenclature_id' => $firstNomenclatureId,
                'sort' => 0,
            ],
            [
                'kit_id' => $kitId,
                'nomenclature_id' => $secondNomenclatureId,
                'sort' => 1,
            ],
        ]);

        $kits = iterator_to_array(app(WarehouseApplicabilityClientInterface::class)->activeApplicabilityKits());

        $this->assertCount(1, $kits);
        $this->assertSame(NomenclatureDetailTemplateEnum::WIPER, $kits[0]->type?->template);
        $this->assertSame(NomenclatureDetailTemplateEnum::SPARK_PLUGS, $kits[0]->nomenclatures[0]->type?->template);
        $this->assertSame(NomenclatureDetailTemplateEnum::SPARK_PLUGS, $kits[0]->nomenclatures[1]->type?->template);
    }

    private function createType(int $id, string $name, ?string $char = null): int
    {
        DB::table('types')->insert([
            'id' => $id,
            'name' => $name,
            'char' => $char,
        ]);

        return $id;
    }

    private function createBrand(): int
    {
        return (int) DB::table('brands')->insertGetId([
            'name' => 'Denso',
            'number_sert' => 'CERT',
            'date_start' => now(),
            'date_end' => now()->addYear(),
            'char' => 'D',
        ]);
    }

    private function createPackDimension(int $typeId): int
    {
        return (int) DB::table('pack_dimensions')->insertGetId([
            'name' => 'Box',
            'weight' => 1,
            'width' => 1,
            'height' => 1,
            'length' => 1,
            'price' => 1,
            'generated' => false,
            'type_id' => $typeId,
        ]);
    }

    private function createKit(int $typeId, int $packDimensionId): int
    {
        return (int) DB::table('kits')->insertGetId([
            'complectation' => 'Комплект',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 100,
            'import_hash' => 'kit-hash',
            'is_sale_separately' => false,
            'is_active' => true,
            'pack_dimension_id' => $packDimensionId,
            'type_id' => $typeId,
        ]);
    }

    private function createNomenclature(int $typeId, int $brandId, string $partNumber): int
    {
        return (int) DB::table('nomenclatures')->insertGetId([
            'type_id' => $typeId,
            'brand_id' => $brandId,
            'name' => $partNumber,
            'country' => 'JP',
            'part_number' => $partNumber,
            'color' => 'Black',
            'weight' => 10,
            'material' => json_encode([]),
            'vehicle_type' => json_encode([]),
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => json_encode([]),
        ]);
    }
}
