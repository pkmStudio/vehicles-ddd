<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Export;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Feature-тест на EngineMultiSheetExport: реальные Repository/Row/Expander/DetailsBuilder,
 * настоящая БД, настоящая генерация xlsx — файл читается обратно через PhpSpreadsheet.
 */
final class EngineMultiSheetExportTest extends TestCase
{
    use RefreshDatabase;

    /**
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
     * Регрессионный тест на баг из refactor-export.md: EngineExportRow клал
     * EngineFuelTypeEnum напрямую в строку без ->value — падало на сериализации.
     *
     * Шаги:
     * 1. Создаёт двигатель с заполненным fuel_type.
     * 2. Зовёт export() и читает основной лист сгенерированного файла.
     * 3. Проверяет, что тип топлива попал в файл строкой ('бензин'), а не объектом enum.
     */
    public function test_exports_main_sheet_without_failing_on_backed_enum_fuel_type(): void
    {
        Storage::fake('local');

        Engine::query()->create([
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'power_kw_start' => 170,
            'power_ps_start' => 231,
            'engine_capacity' => '2979',
            'cylinder_count' => 6,
            'fuel_type' => 'бензин',
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);

        $context = new ExportRunContextDTO(userId: 1, operationId: 'engine-export-main');

        $export = app(EngineMultiSheetExportInterface::class);
        $path = $export->export($context, 'local');

        Storage::disk('local')->assertExists($path);
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));
        [$mainRows] = $this->readSheets($path);

        $this->assertSame(3, $spreadsheet->getSheetCount());
        $this->assertSame('Двигатели', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Свечи зажигания', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('Справочники', $spreadsheet->getSheet(2)->getTitle());
        $this->assertTrue($spreadsheet->getSheet(0)->getStyle('A1')->getFont()->getBold());
        $this->assertContains('Тип топлива', $spreadsheet->getSheet(2)->toArray()[0]);
        $this->assertContains('Размер резьбы', $spreadsheet->getSheet(2)->toArray()[0]);
        $this->assertCount(2, $mainRows); // заголовок + 1 строка
        $this->assertSame('M54B30', $mainRows[1][1]);
        $this->assertSame('бензин', $mainRows[1][3]);
    }

    /**
     * Проверяет лист свечей зажигания: у двигателя без спецификации строка всё равно
     * присутствует (с пустыми details-колонками) — EngineSparkPlugSpecificationRowExpander не
     * пропускает сущности без спецификаций, а отдаёт для них null-заполненную строку.
     *
     * Шаги:
     * 1. Создаёт два двигателя: один со спецификацией свечей, другой — без.
     * 2. Зовёт export() и читает лист свечей зажигания (второй лист).
     * 3. Проверяет, что строк ровно 3 (заголовок + по одной на каждый двигатель).
     * 4. Проверяет, что у двигателя со спецификацией details реально записались.
     */
    public function test_exports_spark_plug_sheet_for_engines_with_and_without_specification(): void
    {
        Storage::fake('local');

        $withSpec = Engine::query()->create([
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'power_kw_start' => 170,
            'power_ps_start' => 231,
            'fuel_type' => 'бензин',
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);
        Engine::query()->create([
            'eng_id' => 600,
            'code_engine' => 'N20B20',
            'power_kw_start' => 135,
            'power_ps_start' => 184,
            'fuel_type' => 'бензин',
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);

        PartSpecification::query()->create([
            'partable_type' => PartableTypeEnum::ENGINE->value,
            'partable_id' => $withSpec->id,
            'template' => DetailTemplateEnum::SPARK_PLUGS->value,
            'details' => [
                'thread' => ['size' => 'M14X125', 'pitch' => 'TP125', 'length' => 'TL19'],
                'electrode' => ['gap' => 'G09'],
                'wrench_jaw_width' => 'WJ19',
            ],
        ]);

        $context = new ExportRunContextDTO(userId: 1, operationId: 'engine-export-spark-plugs');

        $export = app(EngineMultiSheetExportInterface::class);
        $path = $export->export($context, 'local');

        [, $sparkPlugRows] = $this->readSheets($path);

        $this->assertCount(3, $sparkPlugRows); // заголовок + 2 двигателя
        $codes = array_column(array_slice($sparkPlugRows, 1), 1);
        $this->assertEqualsCanonicalizing(['M54B30', 'N20B20'], $codes);
    }
}
