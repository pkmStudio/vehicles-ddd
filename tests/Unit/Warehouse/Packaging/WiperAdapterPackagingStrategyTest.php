<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\WiperAdapterPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class WiperAdapterPackagingStrategyTest extends TestCase
{
    public function test_always_returns_first_box_regardless_of_dimensions(): void
    {
        $strategy = new WiperAdapterPackagingStrategy;

        $first = new PackDimensionData(name: 'Universal', weight: 5, width: 10, height: 10, length: 10, price: 5, typeId: 7, id: 1);
        $second = new PackDimensionData(name: 'Other', weight: 5, width: 999, height: 999, length: 999, price: 5, typeId: 7, id: 2);
        $boxes = new Collection([$first, $second]);

        $nomenclature = new NomenclatureData(partNumber: 'AW-1', quantityInPak: 1, details: []);
        $result = $strategy->calculate(new TypeData(name: 'Адаптер стеклоочистителя', char: 'AW', id: 7), [$nomenclature], $boxes);

        $this->assertSame($first, $result);
    }
}
