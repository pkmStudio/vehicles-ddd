<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Warehouse\Packaging\Application\Services\Strategies\OilFilterPackagingStrategy;
use App\Warehouse\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Packaging\Domain\Exceptions\PackDimensionNotResolvableException;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class OilFilterPackagingStrategyTest extends TestCase
{
    public function test_returns_suitable_box_when_dimensions_fit(): void
    {
        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $strategy = new OilFilterPackagingStrategy($command);

        $nomenclature = new NomenclatureData(
            partNumber: 'OF-1',
            quantityInPak: 1,
            details: ['metrics' => ['length' => [50], 'width' => [40], 'height' => [10]]],
        );
        $box = new PackDimensionData(name: 'Box', weight: 5, width: 60, height: 20, length: 70, price: 5, typeId: 4, id: 5);
        $boxes = new Collection([$box]);

        $result = $strategy->calculate(new TypeData(name: 'Фильтр масляный', id: 4), [$nomenclature], $boxes);

        $this->assertSame($box, $result);
    }

    public function test_throws_when_no_box_fits(): void
    {
        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $strategy = new OilFilterPackagingStrategy($command);

        $nomenclature = new NomenclatureData(
            partNumber: 'OF-2',
            quantityInPak: 1,
            details: ['metrics' => ['length' => [500], 'width' => [400], 'height' => [300]]],
        );
        $box = new PackDimensionData(name: 'TooSmall', weight: 5, width: 10, height: 10, length: 10, price: 5, typeId: 4, id: 6);
        $boxes = new Collection([$box]);

        $this->expectException(PackDimensionNotResolvableException::class);

        $strategy->calculate(new TypeData(name: 'Фильтр масляный', id: 4), [$nomenclature], $boxes);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
