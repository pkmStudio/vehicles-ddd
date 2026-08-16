<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification\Mappers\ModificationTdRowMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Command import модификаций строгий по колонкам, которые всегда заполнены в modifications.csv.
 */
final class ModificationTdRowMapperTest extends TestCase
{
    public function test_maps_row_with_required_columns_present(): void
    {
        $mapper = new ModificationTdRowMapper(new ImportRowValueFormatter);

        $row = $mapper->map([
            10877,
            57501,
            2011,
            null,
            '1.2 4WD',
            91,
            67,
            'Бензиновый двигатель',
            null,
            'Привод на все колеса',
            null,
            4,
            1.2,
            'PC',
        ]);

        $this->assertSame(10877, $row->msId);
        $this->assertSame(57501, $row->modId);
        $this->assertSame(2011, $row->yearFrom);
        $this->assertNull($row->yearTo);
        $this->assertSame('1.2 4WD', $row->description);
        $this->assertSame(91, $row->powerPs);
        $this->assertSame(67, $row->powerKw);
        $this->assertSame('Бензиновый двигатель', $row->engineType);
        $this->assertNull($row->gearType);
        $this->assertSame('PC', $row->type);
        $this->assertSame(['year_from', 'year_to'], $row->toArray()['allow_change_fields']);
    }

    #[DataProvider('requiredColumnProvider')]
    public function test_missing_required_column_throws_validation_exception(int $column, string $field): void
    {
        $mapper = new ModificationTdRowMapper(new ImportRowValueFormatter);
        $row = [
            10877,
            57501,
            2011,
            null,
            '1.2 4WD',
            91,
            67,
            'Бензиновый двигатель',
            null,
            'Привод на все колеса',
            null,
            4,
            1.2,
            'PC',
        ];
        $row[$column] = null;

        $this->expectException(ImportRowValidationException::class);
        $this->expectExceptionMessage("Поле {$field}: обязательно для заполнения.");

        $mapper->map($row);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function requiredColumnProvider(): array
    {
        return [
            'ms_id' => [0, 'ms_id'],
            'mod_id' => [1, 'mod_id'],
            'year_from' => [2, 'year_from'],
            'description' => [4, 'description'],
            'power_ps' => [5, 'power_ps'],
            'power_kw' => [6, 'power_kw'],
            'engine_type' => [7, 'engine_type'],
            'type' => [13, 'type'],
        ];
    }
}
