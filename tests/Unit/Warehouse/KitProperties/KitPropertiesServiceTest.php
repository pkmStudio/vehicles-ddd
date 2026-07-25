<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\KitProperties;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\KitCompositionValidator;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\KitPropertiesService;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\Strategies\SingleTypeStrategy;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\Strategies\WiperWithAdapterStrategy;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Clients\PackagingClientInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitComplectationServiceInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\DTOs\Packaging\PackDimensionDTO;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\PackDimensionNotResolvableException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
use Mockery;
use Psr\Log\NullLogger;
use Tests\TestCase;

final class KitPropertiesServiceTest extends TestCase
{
    private function service(PackagingClientInterface $packaging, KitComplectationServiceInterface $complectation, ?TypeTemplateResolverInterface $wiperResolver = null): KitPropertiesService
    {
        $wiperResolver ??= (function () {
            $resolver = Mockery::mock(TypeTemplateResolverInterface::class);
            $resolver->shouldReceive('resolve')->andReturn(NomenclatureDetailTemplateEnum::BRAKE_PADS);

            return $resolver;
        })();

        return new KitPropertiesService(
            packaging: $packaging,
            complectationService: $complectation,
            compositionValidator: new KitCompositionValidator($wiperResolver, new NullLogger),
            logger: new NullLogger,
            strategies: [new WiperWithAdapterStrategy($wiperResolver), new SingleTypeStrategy],
        );
    }

    public function test_builds_properties_for_single_type_kit(): void
    {
        $type = new TypeData(name: 'Колодки', id: 1);
        $n1 = new NomenclatureData(typeId: 1, partNumber: 'BP-1', quantityInPak: 2, quantityPak: 1, weight: 100, material: ['NICKEL'], details: [], type: $type);
        $n2 = new NomenclatureData(typeId: 1, partNumber: 'BP-2', quantityInPak: 3, quantityPak: 2, weight: 150, material: [], details: [], type: $type);

        $box = new PackDimensionDTO(name: 'Box', weight: 50, width: 10, height: 10, length: 10, price: 5, typeId: 1, id: 99);

        $packaging = Mockery::mock(PackagingClientInterface::class);
        $packaging->shouldReceive('selectOrCreate')
            ->once()
            ->with(
                Mockery::on(fn (TypeData $t): bool => $t->id === 1 && $t->name === 'Колодки'),
                Mockery::on(fn (array $noms): bool => count($noms) === 2 && $noms[0] instanceof NomenclatureData),
            )
            ->andReturn($box);

        $complectation = Mockery::mock(KitComplectationServiceInterface::class);
        $complectation->shouldReceive('describe')
            ->once()
            ->with(5, 'Колодки', ['NICKEL'])
            ->andReturn('В комплекте пять колодок. Материал: Никель');

        $service = $this->service($packaging, $complectation);

        $result = $service->build([$n1, $n2]);

        $this->assertSame(1, $result->typeId);
        $this->assertSame(99, $result->packDimensionId);
        $this->assertSame(300.0, $result->weight);
        $this->assertSame(5, $result->quantityInPackage);
        $this->assertSame(3, $result->quantityPackage);
        $this->assertSame('В комплекте пять колодок. Материал: Никель', $result->complectation);
        $this->assertSame(md5('BP-1|BP-2'), $result->importHash);
    }

    public function test_excludes_adapter_from_primary_but_includes_its_weight(): void
    {
        $wiperType = new TypeData(name: 'Щетки стеклоочистителя', id: 3);
        $adapterType = new TypeData(name: 'Адаптер стеклоочистителя', id: 7);

        $wiper = new NomenclatureData(typeId: 3, partNumber: 'WB-1', quantityInPak: 1, quantityPak: 1, weight: 100, material: [], details: ['category' => 'FRAMELESS'], type: $wiperType, brandId: 10);
        $adapter = new NomenclatureData(typeId: 7, partNumber: 'AW-1', quantityInPak: 5, quantityPak: 5, weight: 20, material: [], details: [], type: $adapterType, brandId: 20);

        $resolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $resolver->shouldReceive('resolve')->with($wiperType)->andReturn(NomenclatureDetailTemplateEnum::WIPER);
        $resolver->shouldReceive('resolve')->with($adapterType)->andReturn(NomenclatureDetailTemplateEnum::WIPER_ADAPTER);

        $box = new PackDimensionDTO(name: 'Box', weight: 10, width: 10, height: 10, length: 10, price: 5, typeId: 3, id: 5);

        $packaging = Mockery::mock(PackagingClientInterface::class);
        $packaging->shouldReceive('selectOrCreate')
            // Только primary (щётка) должна уйти в Packaging — адаптер исключён.
            ->once()
            ->with(
                Mockery::on(fn (TypeData $t): bool => $t->id === 3),
                Mockery::on(fn (array $noms): bool => count($noms) === 1 && $noms[0]->partNumber === 'WB-1'),
            )
            ->andReturn($box);

        $complectation = Mockery::mock(KitComplectationServiceInterface::class);
        $complectation->shouldReceive('describe')->once()->andReturn('В комплекте одна щетка');

        $service = $this->service($packaging, $complectation, $resolver);

        $result = $service->build([$wiper, $adapter]);

        $this->assertSame(3, $result->typeId);
        // quantity — только по primary (щётка): 1, не 1+5.
        $this->assertSame(1, $result->quantityInPackage);
        // вес — по ВСЕМ номенклатурам (100 + 20) + вес коробки (10).
        $this->assertSame(130.0, $result->weight);
    }

    public function test_treats_unresolvable_pack_dimension_as_null(): void
    {
        $type = new TypeData(name: 'Фильтр масляный', id: 4);
        $n = new NomenclatureData(typeId: 4, partNumber: 'OF-1', quantityInPak: 1, quantityPak: 1, weight: 100, material: [], details: [], type: $type);

        $packaging = Mockery::mock(PackagingClientInterface::class);
        $packaging->shouldReceive('selectOrCreate')->once()->andThrow(new PackDimensionNotResolvableException('no fit'));

        $complectation = Mockery::mock(KitComplectationServiceInterface::class);
        $complectation->shouldReceive('describe')->once()->andReturn('');

        $service = $this->service($packaging, $complectation);

        $result = $service->build([$n]);

        $this->assertNull($result->packDimensionId);
        $this->assertSame(100.0, $result->weight);
    }

    public function test_throws_for_empty_nomenclature_list(): void
    {
        $packaging = Mockery::mock(PackagingClientInterface::class);
        $complectation = Mockery::mock(KitComplectationServiceInterface::class);

        $service = $this->service($packaging, $complectation);

        $this->expectException(\InvalidArgumentException::class);
        $service->build([]);
    }

    public function test_throws_when_no_strategy_supports_combination(): void
    {
        $type1 = new TypeData(name: 'Колодки', id: 1);
        $type2 = new TypeData(name: 'Свечи зажигания', id: 2);
        $n1 = new NomenclatureData(typeId: 1, partNumber: 'A', quantityInPak: 1, quantityPak: 1, weight: 1, material: [], details: [], type: $type1);
        $n2 = new NomenclatureData(typeId: 2, partNumber: 'B', quantityInPak: 1, quantityPak: 1, weight: 1, material: [], details: [], type: $type2);

        $resolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $resolver->shouldReceive('resolve')->andReturn(NomenclatureDetailTemplateEnum::BRAKE_PADS, NomenclatureDetailTemplateEnum::SPARK_PLUGS);

        $packaging = Mockery::mock(PackagingClientInterface::class);
        $complectation = Mockery::mock(KitComplectationServiceInterface::class);

        $service = $this->service($packaging, $complectation, $resolver);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Нельзя собрать комплект из разных типов товаров. Исключение: щетка + адаптер.');
        $service->build([$n1, $n2]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
