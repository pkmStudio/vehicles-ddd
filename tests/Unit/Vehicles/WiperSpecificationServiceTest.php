<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Common\Services\WiperSpecificationService;
use App\Vehicles\Domain\Models\PartSpecification;
use Tests\TestCase;

final class WiperSpecificationServiceTest extends TestCase
{
    private WiperSpecificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WiperSpecificationService;
    }

    public function test_detect_side(): void
    {
        $this->assertSame('front', $this->service->detectSide(['front' => []]));
        $this->assertSame('back', $this->service->detectSide(['back' => []]));
        $this->assertNull($this->service->detectSide(['front' => [], 'back' => []]));
        $this->assertNull($this->service->detectSide([]));
    }

    public function test_normalize_adapters(): void
    {
        $this->assertSame(['A', 'B'], $this->service->normalizeAdapters(['A', '', 'B', 'A']));
        $this->assertSame(['X'], $this->service->normalizeAdapters('X'));
        $this->assertSame([], $this->service->normalizeAdapters(null));
        $this->assertSame([], $this->service->normalizeAdapters(['', null]));
    }

    public function test_split_details_separates_sides_and_keeps_single_root_key(): void
    {
        $parts = $this->service->splitDetails([
            'front' => ['adapter_type_front' => ['A1'], 'count_wipers' => 2],
            'back' => ['adapter_type_rear' => ['B1']],
        ]);

        $this->assertCount(2, $parts);
        $this->assertSame('front', $parts[0]['side']);
        $this->assertSame(['front'], array_keys($parts[0]['details']));
        $this->assertSame('back', $parts[1]['side']);
        $this->assertSame(['back'], array_keys($parts[1]['details']));
    }

    public function test_split_details_expands_multiple_adapters_into_separate_records(): void
    {
        $parts = $this->service->splitDetails([
            'front' => ['adapter_type_front' => ['A1', 'A2'], 'count_wipers' => 2],
        ]);

        $this->assertCount(2, $parts);
        $this->assertSame([['A1']], [$parts[0]['details']['front']['adapter_type_front']]);
        $this->assertSame([['A2']], [$parts[1]['details']['front']['adapter_type_front']]);
        // прочие поля сохраняются в каждом варианте
        $this->assertSame(2, $parts[0]['details']['front']['count_wipers']);
    }

    public function test_split_details_skips_empty_side(): void
    {
        $parts = $this->service->splitDetails(['front' => [], 'back' => ['adapter_type_rear' => ['B1']]]);

        $this->assertCount(1, $parts);
        $this->assertSame('back', $parts[0]['side']);
    }

    public function test_split_specification_expands_sides_and_adapters(): void
    {
        $specification = new PartSpecification;
        $specification->id = 123;
        $specification->details = [
            'front' => ['adapter_type_front' => ['A1', 'A2'], 'count_wipers' => 2],
            'back' => ['adapter_type_rear' => 'B1'],
        ];

        $parts = $this->service->splitSpecification($specification);

        $this->assertCount(3, $parts);
        $this->assertSame([123, 123, 123], array_column($parts, 'part_specification_id'));
        $this->assertSame('front', $parts[0]['side']);
        $this->assertSame(['A1'], $parts[0]['details']['front']['adapter_type_front']);
        $this->assertSame('front', $parts[1]['side']);
        $this->assertSame(['A2'], $parts[1]['details']['front']['adapter_type_front']);
        $this->assertSame('back', $parts[2]['side']);
        $this->assertSame(['B1'], $parts[2]['details']['back']['adapter_type_rear']);
    }

    public function test_merge_for_export(): void
    {
        $this->assertSame(
            ['front' => ['x' => 1], 'back' => ['y' => 2]],
            $this->service->mergeForExport(['x' => 1], ['y' => 2]),
        );
        $this->assertSame(['front' => ['x' => 1]], $this->service->mergeForExport(['x' => 1], []));
        $this->assertSame([], $this->service->mergeForExport([], []));
    }
}
