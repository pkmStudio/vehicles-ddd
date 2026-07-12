<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Vehicles\Import\Domain\Contracts\Imports\Command\ManufacturerCommandImportInterface;
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

    /**
     * Проверяет реальный Excel-адаптер (Command/Repository/БД, не моки) на фикстурном CSV.
     *
     * Шаги:
     * 1. Фейкает ManufacturerCommandImported, чтобы не запустить реальный каскад слушателей.
     * 2. Гоняет import() на tests/Fixtures/manufacturers_sample.csv через реальный Command.
     * 3. Проверяет, что в БД ровно 2 производителя с ожидаемыми полями (provider приведён к TD).
     * 4. Проверяет, что событие завершения импорта всё равно продиспатчено.
     */
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
