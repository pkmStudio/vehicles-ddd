<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\ManufacturerImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerImportCompleted;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Manufacturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Внешний (Rabbit/консольный триггер) файловый импорт производителей: mfa_id, name, provider.
 * В отличие от ManufacturerImportTest (консольный TecDoc-каскад, provider всегда TD), здесь
 * все три колонки обязательны — ManufacturerSheetRowDTO строгий, пустой provider не дефолтится,
 * а бракует строку (см. ManufacturerSheetRowMapperTest на уровне мапера).
 */
final class ManufacturerFileImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_manufacturers_with_provider_column_from_csv(): void
    {
        Event::fake([ManufacturerImportCompleted::class]);

        $context = new ImportRunContextDTO(userId: 42, operationId: 'run-manufacturers');
        $path = base_path('tests/Fixtures/manufacturers_provider_sample.csv');

        app(ManufacturerImportInterface::class)->import($path, $context);

        $this->assertSame(2, Manufacturer::query()->count());

        $this->assertDatabaseHas('manufacturers', [
            'mfa_id' => 201,
            'name' => 'Provider Motors',
            'provider' => 'OD',
        ]);

        $this->assertDatabaseHas('manufacturers', [
            'mfa_id' => 202,
            'name' => 'Second Auto',
            'provider' => 'TD',
        ]);

        Event::assertDispatched(
            ManufacturerImportCompleted::class,
            fn (ManufacturerImportCompleted $event): bool => $event->userId === 42 && $event->operationId === 'run-manufacturers',
        );
    }

    public function test_row_with_missing_provider_is_rejected_not_defaulted(): void
    {
        Event::fake([ManufacturerImportCompleted::class]);
        Cache::flush();

        $context = new ImportRunContextDTO(userId: 42, operationId: 'run-manufacturers-missing-provider');
        $path = base_path('tests/Fixtures/manufacturers_missing_provider_sample.csv');

        app(ManufacturerImportInterface::class)->import($path, $context);

        $this->assertDatabaseHas('manufacturers', ['mfa_id' => 203, 'provider' => 'OD']);
        $this->assertDatabaseMissing('manufacturers', ['mfa_id' => 204]);

        $cacheKey = sprintf(
            (string) config('vehicles.import.failures.cache.keys.manufacturer_import_failures'),
            'run-manufacturers-missing-provider',
        );
        $failures = Cache::get($cacheKey, []);

        $this->assertCount(1, $failures);
        $this->assertSame('Производитель', $failures[0]['attribute']);
    }
}
