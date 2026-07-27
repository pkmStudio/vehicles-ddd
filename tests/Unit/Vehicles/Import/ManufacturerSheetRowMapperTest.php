<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\Mappers\ManufacturerSheetRowMapper;
use Tests\TestCase;

/**
 * ManufacturerSheetRowDTO строгий — все три колонки (mfa_id, name, provider) обязательны.
 * Mapper бракует строку с любой пустой колонкой раньше, чем дело дойдёт до сервиса/фабрики.
 */
final class ManufacturerSheetRowMapperTest extends TestCase
{
    public function test_maps_row_with_all_columns_present(): void
    {
        $mapper = new ManufacturerSheetRowMapper(new ImportRowValueFormatter);

        $row = $mapper->map([101, 'Test Motors', 'OD']);

        $this->assertSame(101, $row->mfaId);
        $this->assertSame('Test Motors', $row->name);
        $this->assertSame('OD', $row->provider);
    }

    public function test_missing_provider_throws_validation_exception(): void
    {
        $mapper = new ManufacturerSheetRowMapper(new ImportRowValueFormatter);

        $this->expectException(ImportRowValidationException::class);
        $mapper->map([101, 'Test Motors', null]);
    }

    public function test_missing_mfa_id_throws_validation_exception(): void
    {
        $mapper = new ManufacturerSheetRowMapper(new ImportRowValueFormatter);

        $this->expectException(ImportRowValidationException::class);
        $mapper->map([null, 'Test Motors', 'TD']);
    }

    public function test_missing_name_throws_validation_exception(): void
    {
        $mapper = new ManufacturerSheetRowMapper(new ImportRowValueFormatter);

        $this->expectException(ImportRowValidationException::class);
        $mapper->map([101, null, 'TD']);
    }
}
