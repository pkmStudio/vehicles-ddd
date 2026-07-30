<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\NomenclatureByTypeExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Проверяет xlsx-файл Warehouse-номенклатуры с листом данных и справочников.
 */
final class NomenclatureByTypeExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Читает первые два листа сохранённого xlsx-файла в массивы.
     *
     * @return array{0: array<int, array<int, mixed>>, 1: array<int, array<int, mixed>>}
     */
    private function readSheets(string $path): array
    {
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));

        return [
            $spreadsheet->getSheet(0)->toArray(),
            $spreadsheet->getSheet(1)->toArray(),
        ];
    }

    /**
     * Проверяет экспорт одного типа номенклатуры с detail-колонками и справочным листом.
     */
    public function test_exports_nomenclature_by_type_with_details_and_reference_sheet(): void
    {
        Storage::fake('local');

        $sparkPlugType = Type::query()->create(['name' => 'Свечи зажигания', 'char' => 'SP']);
        $wiperType = Type::query()->create(['name' => 'Щетки стеклоочистителя', 'char' => 'WB']);
        $brand = Brand::query()->create([
            'name' => 'Denso',
            'number_sert' => 'RU-123',
            'date_start' => '2026-01-01 00:00:00',
            'date_end' => '2026-01-02 00:00:00',
            'char' => 'D',
        ]);

        Nomenclature::query()->create([
            'type_id' => $sparkPlugType->id,
            'brand_id' => $brand->id,
            'name' => 'Iridium TT',
            'country' => 'Japan',
            'part_number' => 'IK20TT',
            'color' => 'Silver',
            'weight' => 45,
            'material' => ['IRIDIUM', 'Платина'],
            'vehicle_type' => ['CAR'],
            'quantity_pak' => 1,
            'quantity_in_pak' => 4,
            'details' => [
                'thread' => ['size' => 'M14X125', 'pitch' => 'TP125', 'length' => 'TL19'],
                'electrode' => ['gap' => 'G09', 'count_side' => 'TWO'],
                'wrench_jaw_width' => 'WJ16',
                'tightening_torque' => ['min' => 20, 'max' => 30],
                'metrics' => ['length' => [10], 'width' => [2], 'height' => [2]],
            ],
        ]);

        Nomenclature::query()->create([
            'type_id' => $wiperType->id,
            'brand_id' => $brand->id,
            'name' => 'Wiper',
            'country' => 'China',
            'part_number' => 'W-1',
            'color' => 'Black',
            'weight' => 100,
            'material' => ['RUBBER'],
            'vehicle_type' => ['CAR'],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ]);

        $context = new ExportRunContextDTO(
            userId: 1,
            operationId: 'warehouse-nomenclature',
        );

        $export = app()->makeWith(
            abstract: NomenclatureByTypeExportInterface::class,
            parameters: [
                'typeId' => $sparkPlugType->id,
            ],
        );
        $path = $export->export(
            context: $context,
            disk: 'local',
        );

        Storage::disk('local')->assertExists($path);
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));
        [$dataRows, $referenceRows] = $this->readSheets($path);

        $this->assertSame(2, $spreadsheet->getSheetCount());
        $this->assertSame('Справочники', $spreadsheet->getSheet(1)->getTitle());
        $this->assertTrue($spreadsheet->getSheet(0)->getStyle('A1')->getFont()->getBold());
        $this->assertCount(2, $dataRows);
        $this->assertSame('Тип товара', $dataRows[0][1]);
        $this->assertSame('Свечи зажигания', $dataRows[1][1]);
        $this->assertSame('Denso', $dataRows[1][2]);
        $this->assertSame('IK20TT', $dataRows[1][5]);
        $this->assertSame('Иридий;Платина', $dataRows[1][8]);
        $this->assertSame('Легковые автомобили', $dataRows[1][9]);
        $this->assertSame('M14x1.25', $dataRows[1][12]);
        $this->assertSame('1.25', $dataRows[1][13]);
        $this->assertSame('19', $dataRows[1][14]);
        $this->assertSame('0.9', $dataRows[1][15]);
        $this->assertSame('2', $dataRows[1][16]);
        $this->assertContains('Размер резьбы', $referenceRows[0]);
        $this->assertContains('Свечи зажигания', array_column(array_slice($referenceRows, 1), 0));
    }
}
