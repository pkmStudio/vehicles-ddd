<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\KitProperties;

use App\Warehouse\KitProperties\Application\Services\Strategies\SingleTypeStrategy;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Tests\TestCase;
use UnexpectedValueException;

final class SingleTypeStrategyTest extends TestCase
{
    private function nomenclature(int $typeId, ?TypeData $type = null): NomenclatureData
    {
        return new NomenclatureData(
            typeId: $typeId,
            partNumber: "PN-{$typeId}",
            quantityInPak: 1,
            quantityPak: 1,
            weight: 10,
            material: [],
            details: [],
            type: $type,
        );
    }

    public function test_supports_when_all_nomenclatures_share_one_type(): void
    {
        $strategy = new SingleTypeStrategy;

        $collection = new Collection([$this->nomenclature(1), $this->nomenclature(1)]);

        $this->assertTrue($strategy->supports($collection));
    }

    public function test_does_not_support_multiple_distinct_types(): void
    {
        $strategy = new SingleTypeStrategy;

        $collection = new Collection([$this->nomenclature(1), $this->nomenclature(2)]);

        $this->assertFalse($strategy->supports($collection));
    }

    public function test_resolve_type_returns_first_nomenclature_type(): void
    {
        $strategy = new SingleTypeStrategy;
        $type = new TypeData(name: 'Колодки', id: 1);

        $collection = new Collection([$this->nomenclature(1, $type)]);

        $this->assertSame($type, $strategy->resolveType($collection));
    }

    public function test_resolve_type_throws_when_type_not_loaded(): void
    {
        $strategy = new SingleTypeStrategy;

        $collection = new Collection([$this->nomenclature(1)]);

        $this->expectException(UnexpectedValueException::class);
        $strategy->resolveType($collection);
    }

    public function test_primary_nomenclatures_returns_all(): void
    {
        $strategy = new SingleTypeStrategy;

        $collection = new Collection([$this->nomenclature(1), $this->nomenclature(1)]);

        $this->assertCount(2, $strategy->primaryNomenclatures($collection));
    }
}
