<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Import\Domain\Contracts\Imports\External\EngineSparkPlugSpecificationImportInterface;
use App\Vehicles\Import\Domain\DTOs\ImportRunContextDTO;
use App\Vehicles\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Vehicles\Import\Infrastructure\Models\Engine;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use App\Vehicles\Import\Infrastructure\Models\Modification;
use App\Vehicles\Import\Infrastructure\Models\PartSpecification;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 (баг $this->useCase) и §6.3 (ImportRunContextDTO вместо
 * Auth::id()). Гоняет реальный Excel::import (WithMultipleSheets-самоссылка на CSV).
 */
final class EngineSparkPlugSpecificationImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет внешний (Rabbit/HTTP-триггер) импорт спецификации свечей зажигания «по
     * модификации» — строка файла привязана к модификации, а не к конкретному двигателю;
     * спецификация пишется на все двигатели, связанные с этой модификацией.
     *
     * Шаги:
     * 1. Фейкает EngineImportCompleted.
     * 2. Создаёт производителя → ТС → модификацию → двигатель и привязывает двигатель к
     *    модификации через pivot engine_modification.
     * 3. Собирает ImportRunContextDTO (userId явный) и гоняет import() на
     *    tests/Fixtures/engine_spark_plugs_by_modification_sample.csv.
     * 4. Проверяет, что у двигателя появилась PartSpecification с ожидаемым деревом details.
     * 5. Проверяет, что событие завершения продиспатчено с тем же userId.
     */
    public function test_writes_spark_plug_spec_to_engines_of_modification(): void
    {
        Event::fake([EngineImportCompleted::class]);

        $manufacturer = Manufacturer::query()->create(['mfa_id' => 10, 'name' => 'Skoda', 'provider' => 'TD']);
        $vehicle = Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => 10,
            'ms_id' => 300,
            'name' => 'Octavia',
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
        ]);
        $modification = Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => 300,
            'mod_id' => 50,
            'type' => 'PC',
        ]);
        $engine = Engine::query()->create([
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'eng_fuel_type' => 'бензин',
        ]);
        $engine->modifications()->attach($modification->id, ['eng_id' => 500, 'mod_id' => 50, 'type' => 'PC']);

        $context = new ImportRunContextDTO(userId: 42, runId: 'test-run-spark-by-mod');
        $path = base_path('tests/Fixtures/engine_spark_plugs_by_modification_sample.csv');

        app(EngineSparkPlugSpecificationImportInterface::class)->import($path, $context);

        $spec = PartSpecification::query()->where('partable_id', $engine->id)->where('partable_type', PartableTypeEnum::ENGINE->value)->first();

        $this->assertNotNull($spec);
        $this->assertSame([
            'thread' => ['size' => 'M14X125', 'pitch' => 'TP125', 'length' => 'TL19'],
            'electrode' => ['gap' => 'G09'],
            'wrench_jaw_width' => 'WJ19',
        ], $spec->details);

        Event::assertDispatched(
            EngineImportCompleted::class,
            fn (EngineImportCompleted $event): bool => $event->userId === 42,
        );
    }
}
