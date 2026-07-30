<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\KitExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Проверяет xlsx-файл Warehouse-наборов.
 */
final class KitExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Читает активный лист сохранённого xlsx-файла.
     *
     * @return array<int, array<int, mixed>>
     */
    private function readSheet(string $path): array
    {
        return IOFactory::load(Storage::disk('local')->path($path))->getActiveSheet()->toArray();
    }

    /**
     * Проверяет сортировку состава набора и экспорт флагов в старом формате.
     */
    public function test_exports_kits_with_sorted_part_numbers_and_flags(): void
    {
        Storage::fake('local');

        $type = Type::query()->create(['name' => 'Свечи зажигания', 'char' => 'SP']);
        $brand = Brand::query()->create([
            'name' => 'Denso',
            'number_sert' => 'RU-123',
            'date_start' => '2026-01-01 00:00:00',
            'date_end' => '2026-01-02 00:00:00',
        ]);
        $pack = PackDimension::query()->create([
            'name' => 'Box S',
            'weight' => 10,
            'width' => 10,
            'height' => 5,
            'length' => 20,
            'price' => 15,
            'type_id' => $type->id,
        ]);

        $first = Nomenclature::query()->create([
            'type_id' => $type->id,
            'brand_id' => $brand->id,
            'name' => 'Spark 1',
            'country' => 'Japan',
            'part_number' => 'SP-1',
            'color' => 'Silver',
            'weight' => 45,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ]);
        $second = Nomenclature::query()->create([
            'type_id' => $type->id,
            'brand_id' => $brand->id,
            'name' => 'Spark 2',
            'country' => 'Japan',
            'part_number' => 'SP-2',
            'color' => 'Silver',
            'weight' => 45,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ]);

        $kit = Kit::query()->create([
            'complectation' => 'Комплект свечей',
            'guarantee' => 12,
            'quantity_in_package' => 4,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 180,
            'pack_dimension_id' => $pack->id,
            'type_id' => $type->id,
            'is_sale_separately' => true,
            'is_active' => false,
        ]);
        $kit->nomenclatures()->attach($second->id, ['sort' => 2]);
        $kit->nomenclatures()->attach($first->id, ['sort' => 1]);

        $context = new ExportRunContextDTO(
            userId: 1,
            operationId: 'warehouse-kits',
        );

        $export = app(KitExportInterface::class);
        $path = $export->export(
            context: $context,
            disk: 'local',
        );

        Storage::disk('local')->assertExists($path);
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));
        $rows = $this->readSheet($path);
        $referenceRows = $spreadsheet->getSheet(1)->toArray();

        $this->assertSame(2, $spreadsheet->getSheetCount());
        $this->assertSame('Наборы', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Справочники', $spreadsheet->getSheet(1)->getTitle());
        $this->assertTrue($spreadsheet->getSheet(0)->getStyle('A1')->getFont()->getBold());
        $this->assertCount(2, $rows);
        $this->assertSame('ID комплекта', $rows[0][0]);
        $this->assertSame((string) $kit->id, $rows[1][0]);
        $this->assertSame('SP-1;SP-2', $rows[1][1]);
        $this->assertSame('Да', $rows[1][2]);
        $this->assertSame('Нет', $rows[1][3]);
        $this->assertSame(['Может продаваться отдельно', 'Активен'], $referenceRows[0]);
        $this->assertSame(['Да', 'Да'], $referenceRows[1]);
    }

    /**
     * Проверяет применение явных Kit-фильтров и сортировки из внешнего payload.
     */
    public function test_exports_kits_with_filters_and_sort(): void
    {
        Storage::fake('local');

        $wiperType = Type::query()->create(['name' => 'Щетки стеклоочистителя', 'char' => 'WB']);
        $sparkPlugType = Type::query()->create(['name' => 'Свечи зажигания', 'char' => 'SP']);
        $brand = Brand::query()->create([
            'name' => 'Denso',
            'number_sert' => 'RU-123',
            'date_start' => '2026-01-01 00:00:00',
            'date_end' => '2026-01-02 00:00:00',
        ]);
        $pack = PackDimension::query()->create([
            'name' => 'Box S',
            'weight' => 10,
            'width' => 10,
            'height' => 5,
            'length' => 20,
            'price' => 15,
            'type_id' => $wiperType->id,
        ]);
        $matchedPart = Nomenclature::query()->create([
            'type_id' => $wiperType->id,
            'brand_id' => $brand->id,
            'name' => 'Adapter blade',
            'country' => 'Japan',
            'part_number' => 'AD-1',
            'color' => 'Black',
            'weight' => 45,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ]);
        $excludedPart = Nomenclature::query()->create([
            'type_id' => $sparkPlugType->id,
            'brand_id' => $brand->id,
            'name' => 'Spark 1',
            'country' => 'Japan',
            'part_number' => 'SP-1',
            'color' => 'Silver',
            'weight' => 45,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ]);

        $first = Kit::query()->create([
            'complectation' => 'Adapter set one',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 100,
            'pack_dimension_id' => $pack->id,
            'type_id' => $wiperType->id,
            'is_sale_separately' => true,
            'is_active' => true,
        ]);
        $first->nomenclatures()->attach($matchedPart->id, ['sort' => 1]);

        $second = Kit::query()->create([
            'complectation' => 'Adapter set two',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 100,
            'pack_dimension_id' => $pack->id,
            'type_id' => $wiperType->id,
            'is_sale_separately' => true,
            'is_active' => true,
        ]);
        $second->nomenclatures()->attach($matchedPart->id, ['sort' => 1]);

        $excluded = Kit::query()->create([
            'complectation' => 'Spark kit',
            'guarantee' => 12,
            'quantity_in_package' => 4,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 180,
            'pack_dimension_id' => $pack->id,
            'type_id' => $sparkPlugType->id,
            'is_sale_separately' => true,
            'is_active' => true,
        ]);
        $excluded->nomenclatures()->attach($excludedPart->id, ['sort' => 1]);

        $filters = new KitExportFiltersDTO(
            typeIds: [$wiperType->id],
            isActive: true,
            isSaleSeparately: true,
            nomenclaturePartNumbers: ['AD-1'],
            search: 'Adapter',
        );
        $sort = new KitExportSortDTO(
            field: 'id',
            direction: 'desc',
        );
        $context = new ExportRunContextDTO(
            userId: 1,
            operationId: 'warehouse-kits-filtered',
        );
        $export = app()->makeWith(
            abstract: KitExportInterface::class,
            parameters: [
                'filters' => $filters,
                'sort' => $sort,
            ],
        );

        $path = $export->export(
            context: $context,
            disk: 'local',
        );
        $rows = $this->readSheet($path);

        $this->assertCount(3, $rows);
        $this->assertSame((string) $second->id, $rows[1][0]);
        $this->assertSame((string) $first->id, $rows[2][0]);
    }
}
