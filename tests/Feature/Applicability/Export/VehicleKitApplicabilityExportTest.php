<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Export;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

final class VehicleKitApplicabilityExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_vehicle_kit_applicability_with_reference_sheet(): void
    {
        Storage::fake('local');

        $context = new ExportRunContextDTO(
            userId: 1,
            operationId: 'applicability-export',
        );

        $export = app(VehicleKitApplicabilityExportInterface::class);
        $path = $export->export(
            context: $context,
            disk: 'local',
        );

        Storage::disk('local')->assertExists($path);
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));

        $this->assertSame(2, $spreadsheet->getSheetCount());
        $this->assertSame('Применяемость', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Справочники', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('ID комплекта', $spreadsheet->getSheet(0)->toArray()[0][0]);
        $this->assertSame('Тип кузова', $spreadsheet->getSheet(1)->toArray()[0][0]);
        $this->assertContains('Hatchback', array_column(array_slice($spreadsheet->getSheet(1)->toArray(), 1), 0));
        $this->assertTrue($spreadsheet->getSheet(0)->getStyle('A1')->getFont()->getBold());
    }
}
