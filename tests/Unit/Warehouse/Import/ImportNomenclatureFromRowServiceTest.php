<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Import;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Import\Application\Services\Nomenclature\ImportNomenclatureFromRowService;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

final class ImportNomenclatureFromRowServiceTest extends TestCase
{
    private function validRow(): array
    {
        /** [id, type, brand, name, country, part_number, color, weight, material, vehicle_type, quantity_pak, quantity_in_pak] */
        return ['', 'V-Belt', 'BrandX', 'Test V-Belt', 'RU', 'VB-ART-001', 'Black', '150', 'Никель;неизвестный', 'Легковые автомобили', '2', '3'];
    }

    private function types(): Collection
    {
        return new Collection([new TypeData(name: 'V-Belt', char: 'VB', id: 5)]);
    }

    private function brands(): Collection
    {
        return new Collection([new BrandData(name: 'BrandX', id: 7)]);
    }

    /**
     * Проверяет happy-path: type/brand резолвятся по имени, шаблон — через resolver, details —
     * через локальный Templates-клиент, материал/вид техники переводятся в ключи, новая запись
     * уходит в create.
     */
    public function test_resolves_type_brand_and_creates_when_part_number_is_new(): void
    {
        $expected = $this->dummyNomenclatureData();

        $templateResolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $templateResolver->shouldReceive('resolve')
            ->once()
            ->with(Mockery::on(fn (TypeData $type) => $type->id === 5))
            ->andReturn(NomenclatureDetailTemplateEnum::V_BELT);

        $templates = Mockery::mock(TemplatesClientInterface::class);
        $templates->shouldReceive('buildNomenclatureDetails')
            ->once()
            ->with(NomenclatureDetailTemplateEnum::V_BELT, Mockery::type('array'), Mockery::any())
            ->andReturn([]);

        $command = Mockery::mock(NomenclatureCommandInterface::class);
        $command->shouldNotReceive('updateById');
        $command->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (NomenclatureData $data): bool {
                return $data->id === null
                    && $data->typeId === 5
                    && $data->brandId === 7
                    && $data->partNumber === 'VB-ART-001'
                    && $data->weight === 150
                    && $data->quantityPak === 2
                    && $data->quantityInPak === 3
                    && $data->material === ['NICKEL']
                    && $data->vehicleType === ['CAR']
                    && $data->details === [];
            }))
            ->andReturn($expected);

        $repository = Mockery::mock(NomenclatureRepositoryInterface::class);
        $repository->shouldReceive('findByPartNumber')
            ->once()
            ->with('VB-ART-001')
            ->andReturnNull();

        $service = new ImportNomenclatureFromRowService($repository, $command, $templateResolver, $templates);

        $this->assertSame($expected, $service->importFromRow($this->validRow(), $this->types(), $this->brands()));
    }

    /**
     * Проверяет ветку update: если в строке указан id, запись уходит в updateById, а не в
     * create.
     */
    public function test_updates_by_id_when_id_present_in_row(): void
    {
        $expected = $this->dummyNomenclatureData();

        $templateResolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $templateResolver->shouldReceive('resolve')->once()->andReturn(NomenclatureDetailTemplateEnum::V_BELT);

        $templates = Mockery::mock(TemplatesClientInterface::class);
        $templates->shouldReceive('buildNomenclatureDetails')
            ->once()
            ->andReturn([]);

        $row = $this->validRow();
        $row[0] = '99';

        $command = Mockery::mock(NomenclatureCommandInterface::class);
        $command->shouldNotReceive('create');
        $command->shouldReceive('updateById')
            ->once()
            ->with(Mockery::on(fn (NomenclatureData $data) => $data->id === 99))
            ->andReturn($expected);

        $repository = Mockery::mock(NomenclatureRepositoryInterface::class);
        $repository->shouldNotReceive('findByPartNumber');

        $service = new ImportNomenclatureFromRowService($repository, $command, $templateResolver, $templates);

        $this->assertSame($expected, $service->importFromRow($row, $this->types(), $this->brands()));
    }

    public function test_throws_when_type_not_found(): void
    {
        $service = new ImportNomenclatureFromRowService(
            Mockery::mock(NomenclatureRepositoryInterface::class),
            Mockery::mock(NomenclatureCommandInterface::class),
            Mockery::mock(TypeTemplateResolverInterface::class),
            Mockery::mock(TemplatesClientInterface::class),
        );

        $row = $this->validRow();
        $row[1] = 'Неизвестный тип';

        $this->expectException(InvalidArgumentException::class);

        $service->importFromRow($row, $this->types(), $this->brands());
    }

    public function test_throws_when_brand_not_found(): void
    {
        $service = new ImportNomenclatureFromRowService(
            Mockery::mock(NomenclatureRepositoryInterface::class),
            Mockery::mock(NomenclatureCommandInterface::class),
            Mockery::mock(TypeTemplateResolverInterface::class),
            Mockery::mock(TemplatesClientInterface::class),
        );

        $row = $this->validRow();
        $row[2] = 'Неизвестный бренд';

        $this->expectException(InvalidArgumentException::class);

        $service->importFromRow($row, $this->types(), $this->brands());
    }

    public function test_throws_when_template_not_resolved(): void
    {
        $templateResolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $templateResolver->shouldReceive('resolve')->once()->andReturnNull();

        $service = new ImportNomenclatureFromRowService(
            Mockery::mock(NomenclatureRepositoryInterface::class),
            Mockery::mock(NomenclatureCommandInterface::class),
            $templateResolver,
            Mockery::mock(TemplatesClientInterface::class),
        );

        $this->expectException(InvalidArgumentException::class);

        $service->importFromRow($this->validRow(), $this->types(), $this->brands());
    }

    public function test_throws_when_weight_is_not_positive_integer(): void
    {
        $templateResolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $templateResolver->shouldReceive('resolve')->once()->andReturn(NomenclatureDetailTemplateEnum::V_BELT);

        $templates = Mockery::mock(TemplatesClientInterface::class);
        $templates->shouldReceive('buildNomenclatureDetails')->once()->andReturn([]);

        $command = Mockery::mock(NomenclatureCommandInterface::class);
        $command->shouldNotReceive('create');
        $command->shouldNotReceive('updateById');

        $service = new ImportNomenclatureFromRowService(Mockery::mock(NomenclatureRepositoryInterface::class), $command, $templateResolver, $templates);

        $row = $this->validRow();
        $row[7] = '0';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Вес должен быть положительным целым числом в граммах');

        $service->importFromRow($row, $this->types(), $this->brands());
    }

    private function dummyNomenclatureData(): NomenclatureData
    {
        return new NomenclatureData(
            typeId: 5,
            brandId: 7,
            name: 'Test V-Belt',
            country: 'RU',
            partNumber: 'VB-ART-001',
            color: 'Black',
            weight: 150,
            material: ['NICKEL'],
            vehicleType: ['CAR'],
            quantityPak: 2,
            quantityInPak: 3,
            details: [],
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
