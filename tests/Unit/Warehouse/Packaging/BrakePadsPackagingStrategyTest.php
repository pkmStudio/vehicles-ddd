<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\BrakePadsPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Psr\Log\NullLogger;
use Tests\TestCase;

final class BrakePadsPackagingStrategyTest extends TestCase
{
    public function test_matches_box_by_exact_part_number_for_single_nomenclature(): void
    {
        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $strategy = new BrakePadsPackagingStrategy($command, new NullLogger);

        $nomenclature = new NomenclatureData(partNumber: ' bp-001 ', quantityInPak: 2, details: []);
        $matchingBox = new PackDimensionData(name: 'BP-001', weight: 5, width: 100, height: 30, length: 200, price: 5, typeId: 1, id: 9);
        $packDims = new Collection([$matchingBox]);

        $result = $strategy->calculate(new TypeData(name: 'Колодки', char: 'BP', id: 1), [$nomenclature], $packDims);

        $this->assertSame($matchingBox, $result);
    }

    public function test_falls_back_to_dimensions_multiplying_width_by_quantity(): void
    {
        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $strategy = new BrakePadsPackagingStrategy($command, new NullLogger);

        $nomenclature = new NomenclatureData(
            partNumber: 'BP-999',
            quantityInPak: 2,
            details: ['metrics' => ['length' => [50], 'width' => [20], 'height' => [10]]],
        );
        // Требуется (length=50, width=2*20=40, height=10) — коробка должна влезать и не быть
        // "слишком большой" (зазор > 5мм по любой оси отбраковывает коробку и создаёт новую).
        $box = new PackDimensionData(name: 'Close-fit box', weight: 5, width: 44, height: 14, length: 54, price: 5, typeId: 1, id: 3);
        $packDims = new Collection([$box]);

        $result = $strategy->calculate(new TypeData(name: 'Колодки', char: 'BP', id: 1), [$nomenclature], $packDims);

        $this->assertSame($box, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
