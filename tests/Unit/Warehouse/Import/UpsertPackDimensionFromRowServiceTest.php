<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\Import;

use App\Warehouse\Import\Application\Services\PackDimension\UpsertPackDimensionFromRowService;
use App\Warehouse\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Import\Domain\ModelData\PackDimensionData;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class UpsertPackDimensionFromRowServiceTest extends TestCase
{
    /** [id, name, weight, width, height, length, price, type_id] */
    private function validRow(): array
    {
        return ['', 'Test Box', '150', '20', '30', '40', '500', '5'];
    }

    public function test_upserts_valid_row(): void
    {
        $expected = new PackDimensionData(name: 'Test Box', weight: 150, width: 20, height: 30, length: 40, price: 500, typeId: 5);

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldReceive('upsertById')
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

        $service = new UpsertPackDimensionFromRowService($command);

        $this->assertSame($expected, $service->upsertFromRow($this->validRow()));
    }

    public function test_parses_id_when_present(): void
    {
        $row = $this->validRow();
        $row[0] = '42';

        $command = Mockery::mock(PackDimensionCommandInterface::class);
        $command->shouldReceive('upsertById')
            ->once()
            ->with(Mockery::on(fn (PackDimensionData $data) => $data->id === 42))
            ->andReturn(new PackDimensionData(name: 'Test Box', weight: 150, width: 20, height: 30, length: 40, price: 500, typeId: 5, id: 42));

        $service = new UpsertPackDimensionFromRowService($command);

        $this->assertSame(42, $service->upsertFromRow($row)->id);
    }

    public function test_throws_when_name_empty(): void
    {
        $row = $this->validRow();
        $row[1] = '';

        $service = new UpsertPackDimensionFromRowService(Mockery::mock(PackDimensionCommandInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $service->upsertFromRow($row);
    }

    #[DataProvider('nonPositiveDimensionProvider')]
    public function test_throws_when_dimension_not_positive(int $column): void
    {
        $row = $this->validRow();
        $row[$column] = '0';

        $service = new UpsertPackDimensionFromRowService(Mockery::mock(PackDimensionCommandInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $service->upsertFromRow($row);
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

        $service = new UpsertPackDimensionFromRowService(Mockery::mock(PackDimensionCommandInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $service->upsertFromRow($row);
    }

    public function test_throws_when_type_id_not_positive(): void
    {
        $row = $this->validRow();
        $row[7] = '0';

        $service = new UpsertPackDimensionFromRowService(Mockery::mock(PackDimensionCommandInterface::class));

        $this->expectException(InvalidArgumentException::class);
        $service->upsertFromRow($row);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
