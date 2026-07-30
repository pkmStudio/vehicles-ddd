<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\PackDimensionExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Проверяет xlsx-файл Warehouse-упаковок с листом справочников.
 */
final class PackDimensionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_pack_dimensions_with_readable_type_and_reference_sheet(): void
    {
        Storage::fake('local');

        $brakePadsType = Type::query()->create(['name' => 'Колодки', 'char' => 'BP']);
        $sparkPlugType = Type::query()->create(['name' => 'Свечи зажигания', 'char' => 'SP']);

        $packDimension = PackDimension::query()->create([
            'name' => 'Box S',
            'weight' => 150,
            'width' => 20,
            'height' => 30,
            'length' => 40,
            'price' => 500,
            'generated' => true,
            'type_id' => $brakePadsType->id,
        ]);

        $context = new ExportRunContextDTO(
            userId: 1,
            operationId: 'warehouse-pack-dimensions',
        );

        $path = app(PackDimensionExportInterface::class)->export(
            context: $context,
            disk: 'local',
        );

        Storage::disk('local')->assertExists($path);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));
        $dataRows = $spreadsheet->getSheet(0)->toArray();
        $referenceRows = $spreadsheet->getSheet(1)->toArray();

        $this->assertSame(2, $spreadsheet->getSheetCount());
        $this->assertSame('Упаковки', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Справочники', $spreadsheet->getSheet(1)->getTitle());
        $this->assertTrue($spreadsheet->getSheet(0)->getStyle('A1')->getFont()->getBold());

        $this->assertSame([
            'ID',
            'Название коробки',
            'Вес',
            'Ширина',
            'Высота',
            'Длина',
            'Цена',
            'Тип товара',
            'Код типа',
            'Сгенерирована автоматически',
        ], $dataRows[0]);
        $this->assertSame((string) $packDimension->id, $dataRows[1][0]);
        $this->assertSame('Box S', $dataRows[1][1]);
        $this->assertSame('Колодки', $dataRows[1][7]);
        $this->assertSame('BP', $dataRows[1][8]);
        $this->assertSame('Да', $dataRows[1][9]);

        $this->assertSame(['ID', 'Код типа', 'Тип товара'], $referenceRows[0]);
        $this->assertContains('BP', array_column(array_slice($referenceRows, 1), 1));
        $this->assertContains('SP', array_column(array_slice($referenceRows, 1), 1));
        $this->assertContains($sparkPlugType->name, array_column(array_slice($referenceRows, 1), 2));
    }
}
