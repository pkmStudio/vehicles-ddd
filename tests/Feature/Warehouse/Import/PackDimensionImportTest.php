<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Domain\Events\PackDimensionImportCompleted;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Type;
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
            "id,name,weight,width,height,length,price,type\n,Test Box,150,20,30,40,500,BP\n",
        );

        $context = new ImportRunContextDTO(userId: null, operationId: 'run-pd-test');

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
            "id,name,weight,width,height,length,price,type\n,,150,20,30,40,500,BP\n",
        );

        $context = new ImportRunContextDTO(userId: null, operationId: 'run-pd-fail');

        app(PackDimensionImportInterface::class)->import($path, $context);

        $this->assertSame(0, PackDimension::query()->count());

        $cacheKey = sprintf(
            (string) config('warehouse.import.failures.cache.keys.pack_dimension_import_failures'),
            'run-pd-fail',
        );

        $this->assertNotEmpty(Cache::get($cacheKey));
    }

    public function test_import_accepts_legacy_numeric_type_id(): void
    {
        Event::fake([PackDimensionImportCompleted::class]);

        $type = Type::query()->create(['name' => 'Brake Pad', 'char' => 'BP']);

        $path = $this->writeCsv(
            "id,name,weight,width,height,length,price,type\n,Legacy Type Box,150,20,30,40,500,{$type->id}\n",
        );

        app(PackDimensionImportInterface::class)->import(
            $path,
            new ImportRunContextDTO(userId: null, operationId: 'run-pd-legacy-type-id'),
        );

        $this->assertDatabaseHas('pack_dimensions', [
            'name' => 'Legacy Type Box',
            'type_id' => $type->id,
        ]);
    }

    public function test_import_updates_existing_pack_dimension_by_id(): void
    {
        Event::fake([PackDimensionImportCompleted::class]);

        $type = Type::query()->create(['name' => 'Brake Pad', 'char' => 'BP']);
        $existing = PackDimension::query()->create([
            'name' => 'Old Box',
            'weight' => 100,
            'width' => 10,
            'height' => 10,
            'length' => 10,
            'price' => 100,
            'type_id' => $type->id,
        ]);

        $path = $this->writeCsv(
            "id,name,weight,width,height,length,price,type\n{$existing->id},Updated Box,150,20,30,40,500,BP\n",
        );

        app(PackDimensionImportInterface::class)->import(
            $path,
            new ImportRunContextDTO(userId: null, operationId: 'run-pd-update'),
        );

        $this->assertSame(1, PackDimension::query()->count());
        $this->assertDatabaseHas('pack_dimensions', [
            'id' => $existing->id,
            'name' => 'Updated Box',
            'weight' => 150,
            'width' => 20,
            'height' => 30,
            'length' => 40,
            'price' => 500,
            'type_id' => $type->id,
        ]);
    }
}
