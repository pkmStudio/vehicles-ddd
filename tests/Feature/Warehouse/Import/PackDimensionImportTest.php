<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Warehouse\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Warehouse\Import\Domain\DTOs\ImportRunContextDTO;
use App\Warehouse\Import\Domain\Events\PackDimensionImportCompleted;
use App\Warehouse\Import\Infrastructure\Models\PackDimension;
use App\Warehouse\Import\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class PackDimensionImportTest extends TestCase
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

    /**
     * type_id ссылается на реальную запись Type — фикстура строится динамически, а не как
     * статичный CSV-файл в tests/Fixtures (id генерируется БД).
     */
    private function writeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wpd').'.csv';
        file_put_contents($path, $content);
        $this->tempFile = $path;

        return $path;
    }

    public function test_imports_pack_dimension_from_csv_into_database(): void
    {
        Event::fake([PackDimensionImportCompleted::class]);

        $type = Type::query()->create(['name' => 'Brake Pad', 'char' => 'BP']);

        $path = $this->writeCsv(
            "id,name,weight,width,height,length,price,type_id\n,Test Box,150,20,30,40,500,{$type->id}\n",
        );

        $context = new ImportRunContextDTO(userId: null, runId: 'run-pd-test');

        app(PackDimensionImportInterface::class)->import($path, $context);

        $this->assertDatabaseHas('pack_dimensions', [
            'name' => 'Test Box',
            'weight' => 150,
            'width' => 20,
            'height' => 30,
            'length' => 40,
            'price' => 500,
            'type_id' => $type->id,
        ]);

        Event::assertDispatched(PackDimensionImportCompleted::class);
    }

    public function test_invalid_row_is_recorded_as_failure_and_not_written(): void
    {
        Event::fake([PackDimensionImportCompleted::class]);

        $path = $this->writeCsv(
            "id,name,weight,width,height,length,price,type_id\n,,150,20,30,40,500,1\n",
        );

        $context = new ImportRunContextDTO(userId: null, runId: 'run-pd-fail');

        app(PackDimensionImportInterface::class)->import($path, $context);

        $this->assertSame(0, PackDimension::query()->count());

        $cacheKey = sprintf(
            (string) config('warehouse.import.failures.cache.keys.pack_dimension_import_failures'),
            'run-pd-fail',
        );

        $this->assertNotEmpty(Cache::get($cacheKey));
    }
}
