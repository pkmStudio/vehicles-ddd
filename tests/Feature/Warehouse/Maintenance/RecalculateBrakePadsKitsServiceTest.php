<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Maintenance;

use App\Warehouse\Maintenance\Application\Services\RecalculateBrakePadsKitsService;
use App\Warehouse\Maintenance\Infrastructure\Models\Kit;
use App\Warehouse\Maintenance\Infrastructure\Models\Nomenclature;
use App\Warehouse\Maintenance\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RecalculateBrakePadsKitsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createBrakePadsType(): Type
    {
        return Type::query()->create(['name' => 'Колодки', 'char' => 'BP']);
    }

    private function createBrand(): int
    {
        return DB::table('brands')->insertGetId([
            'name' => 'Bosch',
            'number_sert' => 'CERT-1',
            'date_start' => now(),
            'date_end' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createNomenclature(int $typeId, int $brandId, string $partNumber): Nomenclature
    {
        return Nomenclature::query()->create([
            'type_id' => $typeId,
            'brand_id' => $brandId,
            'name' => "Brake pad {$partNumber}",
            'country' => 'RU',
            'part_number' => $partNumber,
            'color' => 'black',
            'weight' => 100,
            'material' => [],
            'vehicle_type' => [],
            'quantity_pak' => 1,
            'quantity_in_pak' => 2,
            'details' => [],
        ]);
    }

    private function createKit(int $typeId, int $packDimensionId, int $weight): Kit
    {
        return Kit::query()->create([
            'complectation' => 'stale',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => false,
            'weight' => $weight,
            'pack_dimension_id' => $packDimensionId,
            'type_id' => $typeId,
        ]);
    }

    public function test_recalculates_and_updates_kit_with_changed_properties(): void
    {
        $type = $this->createBrakePadsType();
        $brandId = $this->createBrand();
        $n1 = $this->createNomenclature($type->id, $brandId, 'BP-1');
        $n2 = $this->createNomenclature($type->id, $brandId, 'BP-2');

        // Ставим заведомо неверные pack_dimension_id (0) и weight, чтобы гарантированно отличались
        // от пересчитанных значений.
        $kit = $this->createKit($type->id, $this->seedDummyPackDimension($type->id), weight: 1);
        $kit->nomenclatures()->attach([$n1->id => ['sort' => 0], $n2->id => ['sort' => 1]]);

        $summary = app(RecalculateBrakePadsKitsService::class)->recalculate(dryRun: false);

        $this->assertSame(1, $summary['updated']);
        $this->assertSame(0, $summary['unchanged']);
        $this->assertSame(0, $summary['failed']);

        $kit->refresh();
        $this->assertNotSame(1, $kit->weight);
        $this->assertTrue($kit->complement);
        $this->assertNotNull($kit->pack_dimension_id);
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $type = $this->createBrakePadsType();
        $brandId = $this->createBrand();
        $n1 = $this->createNomenclature($type->id, $brandId, 'BP-1');

        $kit = $this->createKit($type->id, $this->seedDummyPackDimension($type->id), weight: 1);
        $kit->nomenclatures()->attach([$n1->id => ['sort' => 0]]);

        $summary = app(RecalculateBrakePadsKitsService::class)->recalculate(dryRun: true);

        $this->assertSame(1, $summary['updated']);

        $kit->refresh();
        $this->assertSame(1, $kit->weight);
        $this->assertSame('stale', $kit->complectation);
    }

    public function test_second_run_reports_kit_as_unchanged(): void
    {
        $type = $this->createBrakePadsType();
        $brandId = $this->createBrand();
        $n1 = $this->createNomenclature($type->id, $brandId, 'BP-1');

        $kit = $this->createKit($type->id, $this->seedDummyPackDimension($type->id), weight: 1);
        $kit->nomenclatures()->attach([$n1->id => ['sort' => 0]]);

        $service = app(RecalculateBrakePadsKitsService::class);
        $service->recalculate(dryRun: false);

        $summary = $service->recalculate(dryRun: false);

        $this->assertSame(0, $summary['updated']);
        $this->assertSame(1, $summary['unchanged']);
    }

    public function test_kit_without_nomenclatures_is_counted_as_failed(): void
    {
        $type = $this->createBrakePadsType();
        $this->createKit($type->id, $this->seedDummyPackDimension($type->id), weight: 1);

        $summary = app(RecalculateBrakePadsKitsService::class)->recalculate(dryRun: false);

        $this->assertSame(1, $summary['failed']);
        $this->assertSame(0, $summary['updated']);
    }

    public function test_returns_zero_summary_when_type_is_missing(): void
    {
        $summary = app(RecalculateBrakePadsKitsService::class)->recalculate(dryRun: false);

        $this->assertSame(['updated' => 0, 'unchanged' => 0, 'failed' => 0], $summary);
    }

    private function seedDummyPackDimension(int $typeId): int
    {
        return (int) DB::table('pack_dimensions')->insertGetId([
            'name' => 'Placeholder',
            'weight' => 1,
            'width' => 1,
            'height' => 1,
            'length' => 1,
            'price' => 1,
            'type_id' => $typeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
