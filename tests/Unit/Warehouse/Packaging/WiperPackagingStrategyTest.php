<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Warehouse\Packaging\Application\Services\Strategies\WiperPackagingStrategy;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class WiperPackagingStrategyTest extends TestCase
{
    private function box(string $name, int $length, int $id): PackDimensionData
    {
        return new PackDimensionData(name: $name, weight: 5, width: 50, height: 20, length: $length, price: 5, typeId: 3, id: $id);
    }

    public function test_returns_shortest_box_that_fits_max_length(): void
    {
        $strategy = new WiperPackagingStrategy;

        $nomenclature = new NomenclatureData(partNumber: 'WB-1', quantityInPak: 1, details: ['length_main' => 500, 'length_second' => 450]);
        $boxes = new Collection([$this->box('L400', 400, 1), $this->box('L550', 550, 2), $this->box('L700', 700, 3)]);

        $result = $strategy->calculate(new TypeData(name: 'Щетки', id: 3), [$nomenclature], $boxes);

        $this->assertSame(2, $result->id);
    }

    public function test_falls_back_to_longest_box_when_none_fit(): void
    {
        $strategy = new WiperPackagingStrategy;

        $nomenclature = new NomenclatureData(partNumber: 'WB-2', quantityInPak: 1, details: ['length_main' => 900, 'length_second' => 0]);
        $boxes = new Collection([$this->box('L400', 400, 1), $this->box('L550', 550, 2)]);

        $result = $strategy->calculate(new TypeData(name: 'Щетки', id: 3), [$nomenclature], $boxes);

        $this->assertSame(2, $result->id);
    }
}
