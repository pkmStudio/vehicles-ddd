<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Application\Services\Kit\ImportKitFromRowService;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\KitProperties\KitPropertiesDTO;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class ImportKitFromRowServiceTest extends TestCase
{
    private function nomenclature(int $id, string $partNumber): NomenclatureData
    {
        return new NomenclatureData(
            typeId: 9,
            brandId: 1,
            name: "Номенклатура {$partNumber}",
            country: 'Россия',
            partNumber: $partNumber,
            color: '',
            weight: 100,
            material: [],
            vehicleType: [],
            quantityInPak: 1,
            quantityPak: 1,
            details: [],
            id: $id,
            type: new TypeData(name: 'Ремень клиновой', id: 9),
        );
    }

    public function test_creates_kit_from_valid_row_when_import_hash_is_new(): void
    {
        $n1 = $this->nomenclature(11, 'A-1');
        $n2 = $this->nomenclature(12, 'A-2');

        $repository = Mockery::mock(NomenclatureRepositoryInterface::class);
        $repository->shouldReceive('findByPartNumbers')
            ->once()
            ->with(['A-1', 'A-2'])
            ->andReturn(new Collection(['A-1' => $n1, 'A-2' => $n2]));

        $properties = new KitPropertiesDTO(
            typeId: 9,
            packDimensionId: 5,
            weight: 200.4,
            quantityInPackage: 2,
            quantityPackage: 2,
            complectation: 'В комплекте два ремня',
            importHash: 'hash-1',
        );

        $kitProperties = Mockery::mock(KitPropertiesClientInterface::class);
        $kitProperties->shouldReceive('build')
            ->once()
            ->with(Mockery::on(fn (array $noms): bool => count($noms) === 2 && $noms[0] instanceof NomenclatureData && $noms[0]->partNumber === 'A-1'))
            ->andReturn($properties);

        $expected = new KitData(
            complectation: 'В комплекте два ремня',
            guarantee: 12,
            quantityInPackage: 2,
            quantityPackage: 2,
            complement: true,
            weight: 200,
            packDimensionId: 5,
            typeId: 9,
            importHash: 'hash-1',
            isSaleSeparately: true,
            isActive: false,
            id: 1,
        );

        $command = Mockery::mock(KitCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->with(
                Mockery::on(function (KitData $data): bool {
                    return $data->complectation === 'В комплекте два ремня'
                        && $data->guarantee === 12
                        && $data->weight === 200
                        && $data->packDimensionId === 5
                        && $data->typeId === 9
                        && $data->complement === true
                        && $data->isSaleSeparately === true
                        && $data->isActive === false;
                }),
                [11, 12],
            )
            ->andReturn($expected);

        $kits = Mockery::mock(KitRepositoryInterface::class);
        $kits->shouldReceive('findByImportHash')
            ->once()
            ->with('hash-1')
            ->andReturnNull();

        $service = new ImportKitFromRowService($repository, $kits, $kitProperties, $command);

        $result = $service->importFromRow(['', 'A-1;A-2', 'Да', 'Нет']);

        $this->assertSame($expected, $result);
    }

    public function test_throws_when_part_number_not_found(): void
    {
        $n1 = $this->nomenclature(11, 'A-1');

        $repository = Mockery::mock(NomenclatureRepositoryInterface::class);
        $repository->shouldReceive('findByPartNumbers')
            ->once()
            ->andReturn(new Collection(['A-1' => $n1]));

        $kitProperties = Mockery::mock(KitPropertiesClientInterface::class);
        $kitProperties->shouldNotReceive('build');

        $command = Mockery::mock(KitCommandInterface::class);
        $command->shouldNotReceive('create');
        $command->shouldNotReceive('updateById');

        $service = new ImportKitFromRowService($repository, Mockery::mock(KitRepositoryInterface::class), $kitProperties, $command);

        $this->expectException(ImportRowValidationException::class);
        $service->importFromRow(['', 'A-1;A-MISSING', 'Нет', 'Да']);
    }

    public function test_throws_when_pack_dimension_not_resolved(): void
    {
        $n1 = $this->nomenclature(11, 'A-1');

        $repository = Mockery::mock(NomenclatureRepositoryInterface::class);
        $repository->shouldReceive('findByPartNumbers')->once()->andReturn(new Collection(['A-1' => $n1]));

        $properties = new KitPropertiesDTO(
            typeId: 9,
            packDimensionId: null,
            weight: 100.0,
            quantityInPackage: 1,
            quantityPackage: 1,
            complectation: '',
            importHash: 'hash-2',
        );

        $kitProperties = Mockery::mock(KitPropertiesClientInterface::class);
        $kitProperties->shouldReceive('build')->once()->andReturn($properties);

        $command = Mockery::mock(KitCommandInterface::class);
        $command->shouldNotReceive('create');
        $command->shouldNotReceive('updateById');

        $service = new ImportKitFromRowService($repository, Mockery::mock(KitRepositoryInterface::class), $kitProperties, $command);

        $this->expectException(ImportRowValidationException::class);
        $service->importFromRow(['', 'A-1', 'Нет', 'Да']);
    }

    public function test_throws_when_part_number_list_is_empty(): void
    {
        $repository = Mockery::mock(NomenclatureRepositoryInterface::class);
        $repository->shouldNotReceive('findByPartNumbers');

        $service = new ImportKitFromRowService(
            $repository,
            Mockery::mock(KitRepositoryInterface::class),
            Mockery::mock(KitPropertiesClientInterface::class),
            Mockery::mock(KitCommandInterface::class),
        );

        $this->expectException(ImportRowValidationException::class);
        $service->importFromRow(['', '', 'Нет', 'Да']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
