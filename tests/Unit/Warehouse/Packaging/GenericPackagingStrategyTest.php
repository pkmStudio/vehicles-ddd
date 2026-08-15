<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\GenericPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class GenericPackagingStrategyTest extends TestCase
{
    public function test_creates_box_with_default_dimensions_when_no_metrics(): void
    {
        $created = new PackDimensionData(
            name: 'Упаковка для Ремень клиновой (д x ш x в) 150 x 100 x 50',
            weight: 5, width: 105, height: 55, length: 155, price: 5, typeId: 9, generated: true, id: 99,
        );

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (PackDimensionData $data): bool {
                return $data->weight === 5
                    && $data->width === 105
                    && $data->height === 55
                    && $data->length === 155
                    && $data->price === 5
                    && $data->typeId === 9
                    && $data->generated === true;
            }))
            ->andReturn($created);

        $strategy = new GenericPackagingStrategy($command);

        $nomenclature = new NomenclatureData(partNumber: 'VB-1', quantityInPak: 1, details: []);
        $result = $strategy->calculate(new TypeData(name: 'Ремень клиновой', char: 'VB', id: 9), [$nomenclature], new Collection);

        $this->assertSame($created, $result);
    }

    public function test_uses_real_metrics_when_present(): void
    {
        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $strategy = new GenericPackagingStrategy($command);

        $nomenclature = new NomenclatureData(
            partNumber: 'TR-1',
            quantityInPak: 1,
            details: ['metrics' => ['length' => [50], 'width' => [40], 'height' => [10]]],
        );
        $box = new PackDimensionData(name: 'Close-fit', weight: 5, width: 44, height: 14, length: 54, price: 5, typeId: 13, id: 8);
        $boxes = new Collection([$box]);

        $result = $strategy->calculate(new TypeData(name: 'Рулевая тяга', char: 'TR', id: 13), [$nomenclature], $boxes);

        $this->assertSame($box, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
