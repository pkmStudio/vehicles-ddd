<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\WiperAdapterAuditExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
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
 * Проверяет xlsx-отчёт аудита адаптеров дворников Warehouse-наборов.
 */
final class WiperAdapterAuditExportTest extends TestCase
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
     * Проверяет старый формат отчёта и текст расхождения адаптеров.
     */
    public function test_exports_wiper_adapter_audit_rows(): void
    {
        Storage::fake('local');

        $wiperType = Type::query()->create(['name' => 'Щетки стеклоочистителя', 'char' => 'WB']);
        $adapterType = Type::query()->create(['name' => 'Адаптер стеклоочистителя', 'char' => 'AW']);
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
        $wiper = Nomenclature::query()->create([
            'type_id' => $wiperType->id,
            'brand_id' => $brand->id,
            'name' => 'Wiper blade',
            'country' => 'Japan',
            'part_number' => 'WB-1',
            'color' => 'Black',
            'weight' => 100,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [
                'adapter_type_front' => ['H'],
            ],
        ]);
        $adapter = Nomenclature::query()->create([
            'type_id' => $adapterType->id,
            'brand_id' => $brand->id,
            'name' => 'Adapter H',
            'country' => 'Japan',
            'part_number' => 'AD-H',
            'color' => 'Black',
            'weight' => 10,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [
                'adapter_type_front' => ['H'],
            ],
        ]);
        $anotherWiper = Nomenclature::query()->create([
            'type_id' => $wiperType->id,
            'brand_id' => $brand->id,
            'name' => 'Wiper blade empty',
            'country' => 'Japan',
            'part_number' => 'WB-2',
            'color' => 'Black',
            'weight' => 100,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [
                'adapter_type_front' => [],
            ],
        ]);
        $kit = Kit::query()->create([
            'complectation' => 'Wiper kit',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 210,
            'pack_dimension_id' => $pack->id,
            'type_id' => $wiperType->id,
            'is_sale_separately' => true,
            'is_active' => true,
        ]);
        $kit->nomenclatures()->attach($wiper->id, ['sort' => 1]);
        $kit->nomenclatures()->attach($adapter->id, ['sort' => 2]);
        $kit->nomenclatures()->attach($anotherWiper->id, ['sort' => 3]);

        $context = new ExportRunContextDTO(
            userId: 1,
            runId: 'warehouse-adapter-audit',
        );
        $export = app(WiperAdapterAuditExportInterface::class);
        $path = $export->export(
            context: $context,
            disk: 'local',
        );
        $rows = $this->readSheet($path);

        $this->assertCount(2, $rows);
        $this->assertSame('ID Набора', $rows[0][0]);
        $this->assertSame((string) $kit->id, $rows[1][0]);
        $this->assertSame('WB-1;AD-H;WB-2', $rows[1][1]);
        $this->assertSame('H', $rows[1][2]);
        $this->assertSame('Тут 1 лишний адаптер: H', $rows[1][3]);
    }
}
