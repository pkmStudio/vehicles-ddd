<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\KitProperties;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\Strategies\WiperWithAdapterStrategy;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class WiperWithAdapterStrategyTest extends TestCase
{
    private function nomenclature(int $typeId, TypeData $type): NomenclatureData
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

    public function test_supports_wiper_with_adapter_combination(): void
    {
        $wiperType = new TypeData(name: 'Щетки стеклоочистителя', id: 3);
        $adapterType = new TypeData(name: 'Адаптер стеклоочистителя', id: 7);

        $resolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $resolver->shouldReceive('resolve')->with($wiperType)->andReturn(NomenclatureDetailTemplateEnum::WIPER);
        $resolver->shouldReceive('resolve')->with($adapterType)->andReturn(NomenclatureDetailTemplateEnum::WIPER_ADAPTER);

        $strategy = new WiperWithAdapterStrategy($resolver);

        $collection = new Collection([
            $this->nomenclature(3, $wiperType),
            $this->nomenclature(7, $adapterType),
        ]);

        $this->assertTrue($strategy->supports($collection));
    }

    public function test_does_not_support_single_type(): void
    {
        $wiperType = new TypeData(name: 'Щетки стеклоочистителя', id: 3);

        $resolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $resolver->shouldReceive('resolve')->with($wiperType)->andReturn(NomenclatureDetailTemplateEnum::WIPER);

        $strategy = new WiperWithAdapterStrategy($resolver);

        $collection = new Collection([$this->nomenclature(3, $wiperType)]);

        $this->assertFalse($strategy->supports($collection));
    }

    public function test_resolve_type_returns_wiper_nomenclature_type(): void
    {
        $wiperType = new TypeData(name: 'Щетки стеклоочистителя', id: 3);
        $adapterType = new TypeData(name: 'Адаптер стеклоочистителя', id: 7);

        $resolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $resolver->shouldReceive('resolve')->with($wiperType)->andReturn(NomenclatureDetailTemplateEnum::WIPER);
        $resolver->shouldReceive('resolve')->with($adapterType)->andReturn(NomenclatureDetailTemplateEnum::WIPER_ADAPTER);

        $strategy = new WiperWithAdapterStrategy($resolver);

        $collection = new Collection([
            $this->nomenclature(7, $adapterType),
            $this->nomenclature(3, $wiperType),
        ]);

        $this->assertSame($wiperType, $strategy->resolveType($collection));
    }

    public function test_primary_nomenclatures_excludes_adapter(): void
    {
        $wiperType = new TypeData(name: 'Щетки стеклоочистителя', id: 3);
        $adapterType = new TypeData(name: 'Адаптер стеклоочистителя', id: 7);

        $resolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $resolver->shouldReceive('resolve')->with($wiperType)->andReturn(NomenclatureDetailTemplateEnum::WIPER);
        $resolver->shouldReceive('resolve')->with($adapterType)->andReturn(NomenclatureDetailTemplateEnum::WIPER_ADAPTER);

        $strategy = new WiperWithAdapterStrategy($resolver);

        $wiper = $this->nomenclature(3, $wiperType);
        $adapter = $this->nomenclature(7, $adapterType);
        $collection = new Collection([$wiper, $adapter]);

        $primary = $strategy->primaryNomenclatures($collection);

        $this->assertCount(1, $primary);
        $this->assertSame($wiper, $primary->first());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
