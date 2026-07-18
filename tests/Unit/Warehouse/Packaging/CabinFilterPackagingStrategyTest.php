<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\CabinFilterPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class CabinFilterPackagingStrategyTest extends TestCase
{
    public function test_returns_first_box_for_hardcoded_part_number_without_dimension_check(): void
    {
        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $strategy = new CabinFilterPackagingStrategy($command);

        $nomenclature = new NomenclatureData(partNumber: 'LAC-513C', quantityInPak: 1, details: []);
        $tinyBox = new PackDimensionData(name: 'Tiny', weight: 5, width: 1, height: 1, length: 1, price: 5, typeId: 6, id: 42);
        $boxes = new Collection([$tinyBox]);

        $result = $strategy->calculate(new TypeData(name: 'Фильтр салонный', id: 6), [$nomenclature], $boxes);

        $this->assertSame($tinyBox, $result);
    }

    public function test_calculates_by_metrics_for_other_part_numbers(): void
    {
        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $strategy = new CabinFilterPackagingStrategy($command);

        $nomenclature = new NomenclatureData(
            partNumber: 'CF-OTHER',
            quantityInPak: 1,
            details: ['metrics' => ['length' => [50], 'width' => [40], 'height' => [10]]],
        );
        $box = new PackDimensionData(name: 'Close-fit', weight: 70, width: 44, height: 14, length: 54, price: 5, typeId: 6, id: 7);
        $boxes = new Collection([$box]);

        $result = $strategy->calculate(new TypeData(name: 'Фильтр салонный', id: 6), [$nomenclature], $boxes);

        $this->assertSame($box, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
