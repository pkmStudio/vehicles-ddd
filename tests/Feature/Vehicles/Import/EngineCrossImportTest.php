<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineCrossImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 (баг $this->useCase) и §6.3 (ImportRunContextDTO вместо
 * Auth::id(), userId обязателен и приходит явно, гейт ">0" убран — событие диспатчится всегда).
 */
final class EngineCrossImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет реальный Excel-адаптер внешнего (Rabbit/HTTP-триггер) кросс-импорта: код
     * двигателя из файла привязывается к group_id.
     *
     * Шаги:
     * 1. Фейкает EngineCrossImportCompleted.
     * 2. Создаёт двигатель с известным code_engine.
     * 3. Собирает ImportRunContextDTO (userId явный — не Auth::id()) и гоняет import() на
     *    tests/Fixtures/engine_groups_sample.csv.
     * 4. Проверяет, что group_id у двигателя обновился на значение из файла.
     * 5. Проверяет, что событие завершения продиспатчено с тем же userId — без гейта ">0".
     */
    public function test_assigns_group_to_engine_by_code(): void
    {
        Event::fake([EngineCrossImportCompleted::class]);

        $engine = Engine::query()->create([
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'power_kw_start' => 170,
            'power_ps_start' => 231,
            'fuel_type' => 'бензин',
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);

        $context = new ImportRunContextDTO(userId: 42, operationId: 'test-run-cross');
        $path = base_path('tests/Fixtures/engine_groups_sample.csv');

        app(EngineCrossImportInterface::class)->import($path, $context);

        $this->assertSame(7, $engine->fresh()->group_id);

        Event::assertDispatched(
            EngineCrossImportCompleted::class,
            fn (EngineCrossImportCompleted $event): bool => $event->userId === 42,
        );
    }
}
