<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineSparkPlugSpecificationImportInterface;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Models\Manufacturer;
use App\Vehicles\Domain\Models\Modification;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для EngineSparkPlugSpecificationImport. Гоняет collection()
 * напрямую с реальным TemplateDataBuilder/UpsertSparkPlugSpecByModificationService/Command.
 */
final class EngineSparkPlugSpecificationImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_writes_spark_plug_spec_to_engines_of_modification(): void
    {
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

        $import = app(EngineSparkPlugSpecificationImportInterface::class);

        // [ms_id, mod_id, thread.size, thread.pitch, thread.length, electrode.gap, wrench_jaw_width]
        $rows = new Collection([
            new Collection([300, 50, 'M14x1.25', '1.25', '19', '0.9', '19']),
        ]);

        $import->collection($rows);

        $spec = PartSpecification::query()->where('partable_id', $engine->id)->where('partable_type', Engine::class)->first();

        $this->assertNotNull($spec);
        $this->assertSame([
            'thread' => ['size' => 'M14X125', 'pitch' => 'TP125', 'length' => 'TL19'],
            'electrode' => ['gap' => 'G09'],
            'wrench_jaw_width' => 'WJ19',
        ], $spec->details);
    }
}
