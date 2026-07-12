<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Packaging\Application\Services\PackagingService;
use App\Warehouse\Packaging\Application\Services\Strategies\AirFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\BrakePadsPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\CabinFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\GenericPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\OilFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\SparkPlugsPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\WiperAdapterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\WiperPackagingStrategy;
use App\Warehouse\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Packaging\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Проверяет диспетчеризацию по detail-шаблону через реальные (не мокнутые — стратегии final)
 * стратегии, различая их по характерному поведению каждой.
 */
final class PackagingServiceTest extends TestCase
{
    /**
     * Собирает сервис с реальными стратегиями поверх мокнутых Repository/TemplateResolver/Command.
     */
    private function makeService(
        PackDimensionRepositoryInterface $repository,
        TypeTemplateResolverInterface $templateResolver,
        PackDimensionCommandInterface $command,
    ): PackagingService {
        return new PackagingService(
            repository: $repository,
            templateResolver: $templateResolver,
            brakePads: new BrakePadsPackagingStrategy($command),
            wiper: new WiperPackagingStrategy,
            cabinFilter: new CabinFilterPackagingStrategy($command),
            oilFilter: new OilFilterPackagingStrategy($command),
            generic: new GenericPackagingStrategy($command),
            sparkPlugs: new SparkPlugsPackagingStrategy,
            wiperAdapter: new WiperAdapterPackagingStrategy,
            airFilter: new AirFilterPackagingStrategy($command),
        );
    }

    public function test_dispatches_wiper_adapter_template_to_wiper_adapter_strategy(): void
    {
        $type = new TypeData(name: 'Адаптер стеклоочистителя', id: 7);
        $first = new PackDimensionData(name: 'First', weight: 5, width: 10, height: 10, length: 10, price: 5, typeId: 7, id: 1);
        $boxes = new Collection([$first, new PackDimensionData(name: 'Second', weight: 5, width: 999, height: 999, length: 999, price: 5, typeId: 7, id: 2)]);

        $repository = Mockery::mock(PackDimensionRepositoryInterface::class);
        $repository->shouldReceive('byType')->once()->with($type)->andReturn($boxes);

        $templateResolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $templateResolver->shouldReceive('resolve')->once()->with($type)->andReturn(NomenclatureDetailTemplateEnum::WIPER_ADAPTER);

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $service = $this->makeService($repository, $templateResolver, $command);

        $nomenclature = new NomenclatureData(partNumber: 'AW-1', quantityInPak: 1, details: []);
        $result = $service->selectOrCreate($type, [$nomenclature]);

        $this->assertSame($first, $result);
    }

    public function test_dispatches_spark_plugs_template_to_spark_plugs_strategy(): void
    {
        $type = new TypeData(name: 'Свечи зажигания', id: 2);
        $smallest = new PackDimensionData(name: 'Small', weight: 5, width: 10, height: 10, length: 10, price: 5, typeId: 2, id: 1);
        $largest = new PackDimensionData(name: 'Large', weight: 5, width: 50, height: 50, length: 50, price: 5, typeId: 2, id: 2);
        $boxes = new Collection([$smallest, $largest]);

        $repository = Mockery::mock(PackDimensionRepositoryInterface::class);
        $repository->shouldReceive('byType')->once()->andReturn($boxes);

        $templateResolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $templateResolver->shouldReceive('resolve')->once()->andReturn(NomenclatureDetailTemplateEnum::SPARK_PLUGS);

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $service = $this->makeService($repository, $templateResolver, $command);

        $nomenclatures = array_fill(0, 3, new NomenclatureData(partNumber: 'SP-1', quantityInPak: 1, details: []));
        $result = $service->selectOrCreate($type, $nomenclatures);

        $this->assertSame(1, $result->id);
    }

    public function test_dispatches_unmapped_template_to_generic_strategy(): void
    {
        $type = new TypeData(name: 'Ремень поликлиновой', id: 17, char: 'ZZ');
        $box = new PackDimensionData(name: 'Close-fit', weight: 5, width: 44, height: 14, length: 54, price: 5, typeId: 17, id: 8);
        $boxes = new Collection([$box]);

        $repository = Mockery::mock(PackDimensionRepositoryInterface::class);
        $repository->shouldReceive('byType')->once()->andReturn($boxes);

        $templateResolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $templateResolver->shouldReceive('resolve')->once()->andReturnNull();

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $service = $this->makeService($repository, $templateResolver, $command);

        $nomenclature = new NomenclatureData(
            partNumber: 'PVB-1',
            quantityInPak: 1,
            details: ['metrics' => ['length' => [50], 'width' => [40], 'height' => [10]]],
        );
        $result = $service->selectOrCreate($type, [$nomenclature]);

        $this->assertSame($box, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
