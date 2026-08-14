<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers\EngineModificationTdRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Command import связей двигатель-модификация строгий: все колонки обязательны в TecDoc cascade.
 */
final class EngineModificationTdRowMapperTest extends TestCase
{
    public function test_maps_row_with_required_columns_present(): void
    {
        $mapper = new EngineModificationTdRowMapper(new ImportRowValueFormatter);

        $row = $mapper->map([500, 50, 'PC']);

        $this->assertSame(500, $row->engId);
        $this->assertSame(50, $row->modId);
        $this->assertSame('PC', $row->type);
    }

    #[DataProvider('requiredColumnProvider')]
    public function test_missing_required_column_throws_validation_exception(int $column, string $field): void
    {
        $mapper = new EngineModificationTdRowMapper(new ImportRowValueFormatter);
        $row = [500, 50, 'PC'];
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
            'mod_id' => [1, 'mod_id'],
            'type' => [2, 'type'],
        ];
    }
}
