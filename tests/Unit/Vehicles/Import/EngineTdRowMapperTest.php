<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers\EngineTdRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Command import двигателей строгий по колонкам, которые всегда заполнены в engines.csv.
 */
final class EngineTdRowMapperTest extends TestCase
{
    public function test_maps_row_with_required_columns_present(): void
    {
        $mapper = new EngineTdRowMapper(new ImportRowValueFormatter);

        $row = $mapper->map([
            30517,
            'LE0',
            175,
            180,
            238,
            245,
            '3.6',
            94,
            6,
            24,
            'бензин',
        ]);

        $this->assertSame(30517, $row->engId);
        $this->assertSame('LE0', $row->codeEngine);
        $this->assertSame(175, $row->powerKwStart);
        $this->assertSame(238, $row->powerPsStart);
        $this->assertSame('бензин', $row->fuelType);
    }

    #[DataProvider('requiredColumnProvider')]
    public function test_missing_required_column_throws_validation_exception(int $column, string $field): void
    {
        $mapper = new EngineTdRowMapper(new ImportRowValueFormatter);
        $row = [
            30517,
            'LE0',
            175,
            null,
            238,
            null,
            '3.6',
            94,
            6,
            24,
            'бензин',
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
            'eng_id' => [0, 'eng_id'],
            'code_engine' => [1, 'code_engine'],
            'power_kw_start' => [2, 'power_kw_start'],
            'power_ps_start' => [4, 'power_ps_start'],
            'fuel_type' => [10, 'fuel_type'],
        ];
    }
}
