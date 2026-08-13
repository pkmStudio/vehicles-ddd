<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Domain\Events\NomenclatureImportCompleted;
use App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Nomenclature\NomenclatureImport;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Type;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class NomenclatureImportTest extends TestCase
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
        $path = tempnam(sys_get_temp_dir(), 'wnom').'.csv';
        file_put_contents($path, $content);
        $this->tempFile = $path;

        return $path;
    }

    /**
     * Проверяет, что queued import adapter и его event listeners сериализуемы.
     */
    public function test_import_adapter_is_serializable_for_queued_chunks(): void
    {
        $import = app(NomenclatureImportInterface::class);

        $this->assertInstanceOf(NomenclatureImport::class, $import);
        $this->assertIsString(serialize($import));

        foreach ($import->registerEvents() as $listener) {
            $this->assertIsString(serialize($listener));
        }
    }

    /**
     * Проверяет реальный Excel-адаптер (Command/Repository/БД, не моки) на фикстурном CSV.
     * Тип "V-Belt" (char=VB) резолвится в generic-шаблон (у него нет полей) — не нужно строить
     * detail-колонки в фикстуре.
     */
    public function test_imports_nomenclature_from_csv_into_database(): void
    {
        Event::fake([NomenclatureCreated::class, NomenclatureImportCompleted::class]);

        Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        Brand::query()->create([
            'name' => 'BrandX',
            'number_sert' => 'CERT-1',
            'date_start' => now(),
            'date_end' => now(),
        ]);

        $path = base_path('tests/Fixtures/warehouse_nomenclature_sample.csv');
        $context = new ImportRunContextDTO(userId: 42, operationId: 'run-nomenclature-test');

        app(NomenclatureImportInterface::class)->import($path, $context);

        $this->assertSame(1, Nomenclature::query()->count());
        $this->assertDatabaseHas('nomenclatures', [
            'part_number' => 'VB-ART-001',
            'name' => 'Test V-Belt',
            'country' => 'RU',
            'color' => 'Black',
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
        ]);

        Event::assertDispatched(NomenclatureImportCompleted::class);
        Event::assertDispatched(NomenclatureCreated::class);
    }

    /**
     * Проверяет, что несуществующий тип не пишет запись, а попадает в failures-cache под ключом
     * прогона (используется дальше ReportImportResultListener'ом).
     */
    public function test_unknown_type_is_recorded_as_failure_and_not_written(): void
    {
        Event::fake([NomenclatureImportCompleted::class]);

        $path = base_path('tests/Fixtures/warehouse_nomenclature_sample.csv');
        $context = new ImportRunContextDTO(userId: null, operationId: 'run-nomenclature-fail');

        app(NomenclatureImportInterface::class)->import($path, $context);

        $this->assertSame(0, Nomenclature::query()->count());

        $cacheKey = sprintf(
            (string) config('warehouse.import.failures.cache.keys.nomenclature_import_failures'),
            'run-nomenclature-fail',
        );

        $this->assertNotEmpty(Cache::get($cacheKey));
    }

    public function test_updates_existing_nomenclature_by_id_from_csv(): void
    {
        Event::fake([NomenclatureUpdated::class, NomenclatureImportCompleted::class]);

        $type = Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        $brand = Brand::query()->create([
            'name' => 'BrandX',
            'number_sert' => 'CERT-1',
            'date_start' => now(),
            'date_end' => now(),
        ]);
        $existing = Nomenclature::query()->create([
            'type_id' => $type->id,
            'brand_id' => $brand->id,
            'name' => 'Old V-Belt',
            'country' => 'RU',
            'part_number' => 'VB-ART-001',
            'color' => 'Black',
            'weight' => 100,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ]);

        $path = $this->writeCsv(
            "id,type,brand,name,country,part_number,color,weight,material,vehicle_type,quantity_pak,quantity_in_pak\n".
            "{$existing->id},V-Belt,BrandX,Updated V-Belt,RU,VB-ART-001,Blue,175,,,2,4\n",
        );

        app(NomenclatureImportInterface::class)->import(
            $path,
            new ImportRunContextDTO(userId: 42, operationId: 'run-nomenclature-update'),
        );

        $this->assertSame(1, Nomenclature::query()->count());
        $this->assertDatabaseHas('nomenclatures', [
            'id' => $existing->id,
            'name' => 'Updated V-Belt',
            'color' => 'Blue',
            'weight' => 175,
            'quantity_pak' => 2,
            'quantity_in_pak' => 4,
        ]);

        Event::assertDispatched(NomenclatureUpdated::class);
    }

    public function test_creates_nomenclature_with_explicit_id_when_row_id_is_new(): void
    {
        Event::fake([NomenclatureCreated::class, NomenclatureImportCompleted::class]);

        Type::query()->create(['name' => 'V-Belt', 'char' => 'VB']);
        Brand::query()->create([
            'name' => 'BrandX',
            'number_sert' => 'CERT-1',
            'date_start' => now(),
            'date_end' => now(),
        ]);

        $path = $this->writeCsv(
            "id,type,brand,name,country,part_number,color,weight,material,vehicle_type,quantity_pak,quantity_in_pak\n".
            "842,V-Belt,BrandX,External V-Belt,RU,VB-EXTERNAL-842,Black,150,,,1,1\n",
        );

        app(NomenclatureImportInterface::class)->import(
            $path,
            new ImportRunContextDTO(userId: 42, operationId: 'run-nomenclature-explicit-id'),
        );

        $this->assertDatabaseHas('nomenclatures', [
            'id' => 842,
            'part_number' => 'VB-EXTERNAL-842',
            'name' => 'External V-Belt',
        ]);

        Event::assertDispatched(NomenclatureCreated::class);
    }

    public function test_wiper_without_category_is_recorded_as_failure_and_not_written(): void
    {
        Event::fake([NomenclatureImportCompleted::class]);

        Type::query()->create(['name' => 'Щетки стеклоочистителя', 'char' => 'WB']);
        Brand::query()->create([
            'name' => 'BrandX',
            'number_sert' => 'CERT-1',
            'date_start' => now(),
            'date_end' => now(),
        ]);

        $path = $this->writeCsv(
            "id,type,brand,name,country,part_number,color,weight,material,vehicle_type,quantity_pak,quantity_in_pak,position,category,construction,season,length_main,length_second,length_rear,adapter_front,adapter_rear,coating,wear_sensor,spoiler,washer_nozzle,heated,steering\n".
            ",Щетки стеклоочистителя,BrandX,Test Wiper,RU,WB-ART-001,Black,150,,,1,1,Переднее,,Бескаркасная,\"На любой сезон, Демисезон\",600,450,,Крючок (Hook / J-Hook),,Графит,Нет,Да,Нет,Нет,Левый руль\n",
        );

        app(NomenclatureImportInterface::class)->import(
            $path,
            new ImportRunContextDTO(userId: null, operationId: 'run-nomenclature-wiper-fail'),
        );

        $this->assertSame(0, Nomenclature::query()->count());

        $cacheKey = sprintf(
            (string) config('warehouse.import.failures.cache.keys.nomenclature_import_failures'),
            'run-nomenclature-wiper-fail',
        );

        $this->assertNotEmpty(Cache::get($cacheKey));
    }
}
