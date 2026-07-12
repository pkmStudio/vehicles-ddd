<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Packaging;

use App\Warehouse\Packaging\Application\Services\Strategies\AirFilterPackagingStrategy;
use App\Warehouse\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class AirFilterPackagingStrategyTest extends TestCase
{
    public function test_predefined_part_number_matches_box_by_canfit_without_oversize_check(): void
    {
        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldNotReceive('create');

        $strategy = new AirFilterPackagingStrategy($command);

        $nomenclature = new NomenclatureData(
            partNumber: 'LA-1019',
            quantityInPak: 1,
            details: ['metrics' => ['length' => [50], 'width' => [40], 'height' => [10]]],
        );
        // Значительно больше требуемого — при обычном (не-predefined) расчёте была бы отбракована
        // как oversize, но у predefined-артикулов зазор вообще не проверяется.
        $box = new PackDimensionData(name: 'Loose fit', weight: 70, width: 90, height: 60, length: 100, price: 5, typeId: 5, id: 4);
        $boxes = new Collection([$box]);

        $result = $strategy->calculate(new TypeData(name: 'Фильтр воздушный', id: 5), [$nomenclature], $boxes);

        $this->assertSame($box, $result);
    }

    public function test_non_predefined_part_number_creates_box_with_15mm_oversize(): void
    {
        $created = new PackDimensionData(name: 'generated', weight: 70, width: 55, height: 25, length: 65, price: 5, typeId: 5, generated: true, id: 11);

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (PackDimensionData $data): bool => $data->width === 55 && $data->height === 25 && $data->length === 65))
            ->andReturn($created);

        $strategy = new AirFilterPackagingStrategy($command);

        $nomenclature = new NomenclatureData(
            partNumber: 'AF-OTHER',
            quantityInPak: 1,
            details: ['metrics' => ['length' => [50], 'width' => [40], 'height' => [10]]],
        );

        $result = $strategy->calculate(new TypeData(name: 'Фильтр воздушный', id: 5), [$nomenclature], new Collection);

        $this->assertSame($created, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
