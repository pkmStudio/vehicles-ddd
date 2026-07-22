<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Application\Services\PackDimension\ImportPackDimensionFromRowService;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ImportPackDimensionFromRowServiceTest extends TestCase
{
    /** [id, name, weight, width, height, length, price, type_id] */
    private function validRow(): array
    {
        return ['', 'Test Box', '150', '20', '30', '40', '500', '5'];
    }

    public function test_creates_valid_row_without_existing_id(): void
    {
        $expected = new PackDimensionData(name: 'Test Box', weight: 150, width: 20, height: 30, length: 40, price: 500, typeId: 5);

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (PackDimensionData $data): bool {
                return $data->id === null
                    && $data->name === 'Test Box'
                    && $data->weight === 150
                    && $data->width === 20
                    && $data->height === 30
                    && $data->length === 40
                    && $data->price === 500
                    && $data->typeId === 5;
            }))
            ->andReturn($expected);

        $repository = Mockery::mock(PackDimensionRepositoryInterface::class);
        $repository->shouldNotReceive('findById');

        $service = new ImportPackDimensionFromRowService($repository, $command);

        $this->assertSame($expected, $service->importFromRow($this->validRow()));
    }

    public function test_updates_when_id_exists(): void
    {
        $row = $this->validRow();
        $row[0] = '42';

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldReceive('updateById')
            ->once()
            ->with(Mockery::on(fn (PackDimensionData $data) => $data->id === 42))
            ->andReturn(new PackDimensionData(name: 'Test Box', weight: 150, width: 20, height: 30, length: 40, price: 500, typeId: 5, id: 42));

        $repository = Mockery::mock(PackDimensionRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(42)
            ->andReturn(new PackDimensionData(name: 'Old Box', weight: 100, width: 10, height: 10, length: 10, price: 100, typeId: 5, id: 42));

        $service = new ImportPackDimensionFromRowService($repository, $command);

        $this->assertSame(42, $service->importFromRow($row)->id);
    }

    public function test_throws_when_name_empty(): void
    {
        $row = $this->validRow();
        $row[1] = '';

        $service = new ImportPackDimensionFromRowService(Mockery::mock(PackDimensionRepositoryInterface::class), Mockery::mock(PackDimensionCommandInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $service->importFromRow($row);
    }

    #[DataProvider('nonPositiveDimensionProvider')]
    public function test_throws_when_dimension_not_positive(int $column): void
    {
        $row = $this->validRow();
        $row[$column] = '0';

        $service = new ImportPackDimensionFromRowService(Mockery::mock(PackDimensionRepositoryInterface::class), Mockery::mock(PackDimensionCommandInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $service->importFromRow($row);
    }

    /**
     * @return array<int, array{int}>
     */
    public static function nonPositiveDimensionProvider(): array
    {
        return [
            'weight' => [2],
            'width' => [3],
            'height' => [4],
            'length' => [5],
        ];
    }

    public function test_throws_when_price_negative(): void
    {
        $row = $this->validRow();
        $row[6] = '-1';

        $service = new ImportPackDimensionFromRowService(Mockery::mock(PackDimensionRepositoryInterface::class), Mockery::mock(PackDimensionCommandInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $service->importFromRow($row);
    }

    public function test_throws_when_type_id_not_positive(): void
    {
        $row = $this->validRow();
        $row[7] = '0';

        $service = new ImportPackDimensionFromRowService(Mockery::mock(PackDimensionRepositoryInterface::class), Mockery::mock(PackDimensionCommandInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $service->importFromRow($row);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
