<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Warehouse\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Warehouse\Import\Domain\DTOs\ImportRunContextDTO;
use App\Warehouse\Import\Domain\Events\KitImportCompleted;
use App\Warehouse\Import\Infrastructure\Models\Brand;
use App\Warehouse\Import\Infrastructure\Models\Kit;
use App\Warehouse\Import\Infrastructure\Models\Nomenclature;
use App\Warehouse\Import\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class KitImportTest extends TestCase
{
    use RefreshDatabase;

    private ?string $tempFile = null;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        if ($this->tempFile !== null && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        Cache::flush();
        parent::tearDown();
    }

    private function writeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wkit').'.csv';
        file_put_contents($path, $content);
        $this->tempFile = $path;

        return $path;
    }

    private function createNomenclature(int $typeId, int $brandId, string $partNumber): Nomenclature
    {
        return Nomenclature::query()->create([
            'type_id' => $typeId,
            'brand_id' => $brandId,
            'name' => "Test {$partNumber}",
            'country' => 'RU',
            'part_number' => $partNumber,
            'color' => 'Black',
            'weight' => 100,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ]);
    }

    /**
     * V-Belt/generic — единственный тип без detail-колонок (details пустой), не требует фикстур
     * под шаблон Templates, поэтому Packaging использует DEFAULT_*-габариты и создаёт коробку сам.
     */
    public function test_imports_kit_from_csv_into_database(): void
    {
        Event::fake([KitImportCompleted::class]);

        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create([
            'name' => 'BrandX',
            'number_sert' => 'CERT-1',
            'date_start' => now(),
            'date_end' => now(),
        ]);

        $n1 = $this->createNomenclature($type->id, $brand->id, 'VB-1');
        $n2 = $this->createNomenclature($type->id, $brand->id, 'VB-2');

        $path = $this->writeCsv("id,part_numbers,is_sale_separately,is_active\n,VB-1;VB-2,Да,Нет\n");

        $context = new ImportRunContextDTO(userId: null, runId: 'run-kit-test');
        app(KitImportInterface::class)->import($path, $context);

        $this->assertSame(1, Kit::query()->count());

        $kit = Kit::query()->sole();
        $this->assertSame($type->id, $kit->type_id);
        $this->assertTrue($kit->complement);
        $this->assertTrue($kit->is_sale_separately);
        $this->assertFalse($kit->is_active);

        $this->assertDatabaseHas('kit_nomenclature', ['kit_id' => $kit->id, 'nomenclature_id' => $n1->id, 'sort' => 0]);
        $this->assertDatabaseHas('kit_nomenclature', ['kit_id' => $kit->id, 'nomenclature_id' => $n2->id, 'sort' => 1]);

        Event::assertDispatched(KitImportCompleted::class);
    }

    public function test_unknown_part_number_is_recorded_as_failure_and_not_written(): void
    {
        Event::fake([KitImportCompleted::class]);

        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create([
            'name' => 'BrandX',
            'number_sert' => 'CERT-1',
            'date_start' => now(),
            'date_end' => now(),
        ]);
        $this->createNomenclature($type->id, $brand->id, 'VB-1');

        $path = $this->writeCsv("id,part_numbers,is_sale_separately,is_active\n,VB-1;VB-MISSING,Нет,Да\n");

        $context = new ImportRunContextDTO(userId: null, runId: 'run-kit-fail');
        app(KitImportInterface::class)->import($path, $context);

        $this->assertSame(0, Kit::query()->count());

        $cacheKey = sprintf(
            (string) config('warehouse.import.failures.cache.keys.kit_import_failures'),
            'run-kit-fail',
        );
        $this->assertNotEmpty(Cache::get($cacheKey));
    }
}
