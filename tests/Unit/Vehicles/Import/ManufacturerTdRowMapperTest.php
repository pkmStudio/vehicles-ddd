<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\Mappers\ManufacturerTdRowMapper;
use Tests\TestCase;

/**
 * Command import производителей строгий: `mfa_id` и `name` обязательны в TecDoc cascade.
 */
final class ManufacturerTdRowMapperTest extends TestCase
{
    public function test_maps_row_with_required_columns_present(): void
    {
        $mapper = new ManufacturerTdRowMapper(new ImportRowValueFormatter);

        $row = $mapper->map([101, 'Test Motors']);

        $this->assertSame(101, $row->mfaId);
        $this->assertSame('Test Motors', $row->name);
    }

    public function test_missing_mfa_id_throws_validation_exception(): void
    {
        $mapper = new ManufacturerTdRowMapper(new ImportRowValueFormatter);

        $this->expectException(ImportRowValidationException::class);
        $this->expectExceptionMessage('Поле mfa_id: обязательно для заполнения.');

        $mapper->map([null, 'Test Motors']);
    }

    public function test_invalid_mfa_id_throws_validation_exception(): void
    {
        $mapper = new ManufacturerTdRowMapper(new ImportRowValueFormatter);

        $this->expectException(ImportRowValidationException::class);
        $this->expectExceptionMessage('Поле mfa_id: ожидалось целое число.');

        $mapper->map(['abc', 'Test Motors']);
    }

    public function test_missing_name_throws_validation_exception(): void
    {
        $mapper = new ManufacturerTdRowMapper(new ImportRowValueFormatter);

        $this->expectException(ImportRowValidationException::class);
        $this->expectExceptionMessage('Поле name: обязательно для заполнения.');

        $mapper->map([101, null]);
    }
}
