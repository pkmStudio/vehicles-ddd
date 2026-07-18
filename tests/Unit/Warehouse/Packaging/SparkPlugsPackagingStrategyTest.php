<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\SparkPlugsPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class SparkPlugsPackagingStrategyTest extends TestCase
{
    private function boxes(): Collection
    {
        return new Collection([
            new PackDimensionData(name: 'Small', weight: 5, width: 10, height: 10, length: 10, price: 5, typeId: 2, id: 1),
            new PackDimensionData(name: 'Large', weight: 5, width: 50, height: 50, length: 50, price: 5, typeId: 2, id: 2),
        ]);
    }

    private function nomenclature(): NomenclatureData
    {
        return new NomenclatureData(partNumber: 'SP-1', quantityInPak: 1, details: []);
    }

    public function test_returns_largest_box_when_count_above_threshold(): void
    {
        $strategy = new SparkPlugsPackagingStrategy;

        $nomenclatures = array_fill(0, 7, $this->nomenclature());
        $result = $strategy->calculate(new TypeData(name: 'Свечи зажигания', id: 2), $nomenclatures, $this->boxes());

        $this->assertSame(2, $result->id);
    }

    public function test_returns_smallest_box_when_count_at_or_below_threshold(): void
    {
        $strategy = new SparkPlugsPackagingStrategy;

        $nomenclatures = array_fill(0, 6, $this->nomenclature());
        $result = $strategy->calculate(new TypeData(name: 'Свечи зажигания', id: 2), $nomenclatures, $this->boxes());

        $this->assertSame(1, $result->id);
    }
}
