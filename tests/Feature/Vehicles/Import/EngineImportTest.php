<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineCommandImported;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для EngineCommandImport.
 */
final class EngineImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет реальный Excel-адаптер (Command/Repository/БД, не моки) на фикстурном CSV.
     *
     * Шаги:
     * 1. Фейкает EngineCommandImported, чтобы не запустить реальный каскад слушателей.
     * 2. Гоняет import() на tests/Fixtures/engines_sample.csv через реальный Command.
     * 3. Проверяет, что в БД появился ровно один двигатель с ожидаемыми полями.
     * 4. Проверяет, что событие завершения импорта продиспатчено.
     */
    public function test_imports_engines_from_csv_into_database(): void
    {
        Event::fake([EngineCommandImported::class]);

        $path = base_path('tests/Fixtures/engines_sample.csv');

        app(EngineCommandImportInterface::class)->import($path);

        $this->assertSame(1, Engine::query()->count());

        $this->assertDatabaseHas('engines', [
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'cylinder_count' => 6,
            'eng_fuel_type' => 'бензин',
        ]);

        Event::assertDispatched(EngineCommandImported::class);
    }
}
