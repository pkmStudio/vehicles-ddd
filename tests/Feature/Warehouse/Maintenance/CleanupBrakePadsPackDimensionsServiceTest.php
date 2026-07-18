<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Maintenance;

use App\Modules\Warehouse\Features\Maintenance\Application\Services\CleanupBrakePadsPackDimensionsService;
use App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CleanupBrakePadsPackDimensionsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createBrakePadsType(): Type
    {
        return Type::query()->create(['name' => 'Колодки', 'char' => 'BP']);
    }

    private function createPackDimension(int $typeId, string $name = 'Box'): PackDimension
    {
        return PackDimension::query()->create([
            'name' => $name,
            'weight' => 5,
            'width' => 44,
            'height' => 14,
            'length' => 54,
            'price' => 5,
            'type_id' => $typeId,
        ]);
    }

    public function test_deletes_pack_dimension_not_used_by_any_kit(): void
    {
        $type = $this->createBrakePadsType();
        $unused = $this->createPackDimension($type->id, 'Unused');

        $summary = app(CleanupBrakePadsPackDimensionsService::class)->cleanup(dryRun: false);

        $this->assertSame(1, $summary['deleted']);
        $this->assertSame(0, $summary['skipped']);
        $this->assertModelMissing($unused);
    }

    public function test_keeps_pack_dimension_used_by_a_kit(): void
    {
        $type = $this->createBrakePadsType();
        $used = $this->createPackDimension($type->id, 'Used');

        Kit::query()->create([
            'complectation' => 'test',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => false,
            'weight' => 100,
            'pack_dimension_id' => $used->id,
            'type_id' => $type->id,
        ]);

        $summary = app(CleanupBrakePadsPackDimensionsService::class)->cleanup(dryRun: false);

        $this->assertSame(0, $summary['deleted']);
        $this->assertModelExists($used);
    }

    public function test_dry_run_reports_candidates_without_deleting(): void
    {
        $type = $this->createBrakePadsType();
        $unused = $this->createPackDimension($type->id, 'Unused');

        $summary = app(CleanupBrakePadsPackDimensionsService::class)->cleanup(dryRun: true);

        $this->assertSame(0, $summary['deleted']);
        $this->assertTrue($summary['candidates']->contains('id', $unused->id));
        $this->assertModelExists($unused);
    }

    public function test_returns_zero_summary_when_type_is_missing(): void
    {
        $summary = app(CleanupBrakePadsPackDimensionsService::class)->cleanup(dryRun: false);

        $this->assertSame(0, $summary['candidates']->count());
        $this->assertSame(0, $summary['deleted']);
        $this->assertSame(0, $summary['skipped']);
    }
}
