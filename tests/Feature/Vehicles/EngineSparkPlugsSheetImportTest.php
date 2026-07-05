<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Infrastructure\Imports\Engine\Sheets\EngineSparkPlugsSheetImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для EngineSparkPlugsSheetImport (лист «свечи зажигания»
 * по конкретному двигателю, в отличие от EngineSparkPlugSpecificationImport — по модификации).
 */
final class EngineSparkPlugsSheetImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_writes_spark_plug_spec_for_engine(): void
    {
        $engine = Engine::query()->create(['eng_id' => 500, 'code_engine' => 'M54B30']);

        /** @var EngineSparkPlugsSheetImport $import */
        $import = app()->makeWith(EngineSparkPlugsSheetImport::class, [
            'userId' => 1,
            'cacheKey' => 'engine_spark_plugs_sheet_test',
        ]);

        // index 0 = eng_id, 1-8 = не относящиеся к тесту колонки, 9+ = thread.size/pitch/length, electrode.gap, wrench_jaw_width
        $rows = new Collection([
            new Collection([500, '', '', '', '', '', '', '', '', 'M14x1.25', '1.25', '19', '0.9', '19']),
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
