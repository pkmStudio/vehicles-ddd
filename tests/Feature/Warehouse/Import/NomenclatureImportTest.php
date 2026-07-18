<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Domain\Events\NomenclatureImportCompleted;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Type;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class NomenclatureImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
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
        $context = new ImportRunContextDTO(userId: 42, runId: 'run-nomenclature-test');

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
        $context = new ImportRunContextDTO(userId: null, runId: 'run-nomenclature-fail');

        app(NomenclatureImportInterface::class)->import($path, $context);

        $this->assertSame(0, Nomenclature::query()->count());

        $cacheKey = sprintf(
            (string) config('warehouse.import.failures.cache.keys.nomenclature_import_failures'),
            'run-nomenclature-fail',
        );

        $this->assertNotEmpty(Cache::get($cacheKey));
    }
}
