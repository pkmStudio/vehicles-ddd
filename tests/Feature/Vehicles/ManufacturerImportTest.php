<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Import\Domain\Contracts\Imports\ManufacturerCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Регрессионный тест на баг из plan.md §6.1: адаптеры импорта обращались к необъявленному
 * $this->useCase вместо $this->service — Excel::import падал на первой же строке. Гоняет
 * реальный Excel-адаптер на маленьком фикстурном CSV поверх настоящего Command/Repository.
 *
 * ManufacturerCommandImported фейкается точечно, чтобы не запускать реальный каскад
 * (Start*ImportListener на storage/vehicles/{vehicles,engines}.csv) — тест проверяет только
 * этот один шаг импорта, не весь пайплайн.
 */
final class ManufacturerImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_manufacturers_from_csv_into_database(): void
    {
        Event::fake([ManufacturerCommandImported::class]);

        $path = base_path('tests/Fixtures/manufacturers_sample.csv');

        app(ManufacturerCommandImportInterface::class)->import($path);

        $this->assertSame(2, Manufacturer::query()->count());

        $this->assertDatabaseHas('manufacturers', [
            'mfa_id' => 101,
            'name' => 'Test Motors',
            'provider' => 'TD',
        ]);

        $this->assertDatabaseHas('manufacturers', [
            'mfa_id' => 102,
            'name' => 'Sample Auto',
            'provider' => 'TD',
        ]);

        Event::assertDispatched(ManufacturerCommandImported::class);
    }
}
