<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\MoySklad;

use App\Modules\Warehouse\Features\MoySklad\Application\Services\NomenclatureBackfillService;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Jobs\SyncNomenclatureJob;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Проверяет массовую постановку обычных sync-задач для backfill МойСклад.
 */
final class NomenclatureBackfillServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет, что backfill ставит SyncNomenclatureJob для каждой номенклатуры.
     */
    public function test_backfill_dispatches_sync_job_for_each_nomenclature(): void
    {
        Queue::fake();

        $this->createNomenclature('BP-1');
        $this->createNomenclature('BP-2');

        app(NomenclatureBackfillService::class)->execute(chunk: 1);

        Queue::assertPushed(SyncNomenclatureJob::class, 2);
    }

    /**
     * Создаёт минимальную Warehouse-номенклатуру с типом и брендом.
     */
    private function createNomenclature(string $partNumber): Nomenclature
    {
        $type = Type::query()->create(['name' => 'Brake Pads', 'char' => 'BP']);
        $brand = Brand::query()->create([
            'name' => "Bosch {$partNumber}",
            'number_sert' => "CERT-{$partNumber}",
            'date_start' => now(),
            'date_end' => now(),
            'char' => 'B',
        ]);

        return Nomenclature::query()->create([
            'type_id' => $type->id,
            'brand_id' => $brand->id,
            'name' => "Part {$partNumber}",
            'country' => 'RU',
            'part_number' => $partNumber,
            'color' => 'Black',
            'weight' => 100,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 1,
            'details' => [],
        ]);
    }
}
