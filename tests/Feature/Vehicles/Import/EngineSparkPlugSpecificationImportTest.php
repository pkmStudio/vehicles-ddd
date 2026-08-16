<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineSparkPlugSpecificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\EngineSparkPlugSpecificationImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
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
     * Проверяет, что queued import adapter не держит несериализуемые зависимости.
     */
    public function test_import_adapter_is_serializable_for_queued_chunks(): void
    {
        $import = app(EngineSparkPlugSpecificationImportInterface::class);

        $this->assertInstanceOf(EngineSparkPlugSpecificationImport::class, $import);
        $this->assertIsString(serialize($import));

        foreach ($import->registerEvents() as $listener) {
            $this->assertIsString(serialize($listener));
        }
    }

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
            'generation' => 'III',
            'generation_year_from' => 2013,
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
            'provider' => ProviderEnum::TD->value,
            'steering_type' => 'Левый руль',
            'is_allow' => true,
        ]);
        $modification = Modification::query()->create([
            'vehicle_id' => $vehicle->id,
            'ms_id' => 300,
            'mod_id' => 50,
            'type' => 'PC',
            'year_from' => 2013,
            'description' => '1.4 TSI',
            'power_ps' => 150,
            'power_kw' => 110,
            'engine_type' => EngineTypeEnum::PETROL->value,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => ['year_from', 'year_to'],
        ]);
        $engine = Engine::query()->create([
            'eng_id' => 500,
            'code_engine' => 'M54B30',
            'power_kw_start' => 170,
            'power_ps_start' => 231,
            'fuel_type' => 'бензин',
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ]);
        $engine->modifications()->attach($modification->id, ['eng_id' => 500, 'mod_id' => 50, 'type' => 'PC']);

        $context = new ImportRunContextDTO(userId: 42, operationId: 'test-run-spark-by-mod');
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
