<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Import\Application\Factories\EngineDataFactory;
use App\Vehicles\Import\Application\Services\Engine\UpsertEngineFromSheetService;
use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Vehicles\Import\Domain\ModelData\EngineData;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * Построчный сценарий импорта двигателя. Command мокается — БД не нужна.
 */
final class UpsertEngineFromSheetServiceTest extends TestCase
{
    /**
     * Проверяет happy-path: валидная строка маппится в EngineData (включая приведение
     * eng_fuel_type к enum) и уходит в Command::upsertByEngId.
     *
     * Шаги:
     * 1. Мокает EngineCommandInterface::upsertByEngId — перехватывает переданный EngineData
     *    и возвращает заранее известный результат.
     * 2. Зовёт upsertFromRow() с валидным EngineSheetRowDTO.
     * 3. Проверяет, что вернулся именно ожидаемый результат Command.
     * 4. Проверяет перехваченные поля EngineData (engId/codeEngine/кВт/цилиндры/enum топлива).
     */
    public function test_maps_row_and_upserts_via_command(): void
    {
        $captured = null;
        $expected = new EngineData(engId: 101);

        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldReceive('upsertByEngId')
            ->once()
            ->with(Mockery::on(function (EngineData $data) use (&$captured) {
                $captured = $data;

                return true;
            }))
            ->andReturn($expected);

        $service = new UpsertEngineFromSheetService($command, new EngineDataFactory);

        // [eng_id, code_engine, kw_start, kw_upto, ps_start, ps_upto, capacity, cyl_diam, cyl_count, valves, fuel]
        $result = $service->upsertFromRow(new EngineSheetRowDTO(
            engId: 101,
            codeEngine: 'M54B30',
            engPowerKwStart: 170,
            engPowerKwUpto: null,
            engPowerPsStart: 231,
            engPowerPsUpto: null,
            engineCapacity: '2979',
            cylinderDiameter: 3.0,
            cylinderCount: 6,
            engNumberOfValves: 24,
            engFuelType: 'бензин',
        ));

        $this->assertSame($expected, $result);
        $this->assertSame(101, $captured->engId);
        $this->assertSame('M54B30', $captured->codeEngine);
        $this->assertSame(170, $captured->engPowerKwStart);
        $this->assertSame(6, $captured->cylinderCount);
        $this->assertSame(EngineFuelTypeEnum::PETROL, $captured->engFuelType);
    }

    /**
     * Проверяет грабли из ARCHITECTURE.md: невалидное сырое значение enum-поля (топливо
     * 'плазма') должно валиться на валидации, а не тихо стать null через tryFrom.
     *
     * Шаги:
     * 1. Мокает Command — ожидает, что upsertByEngId НЕ вызовется.
     * 2. Зовёт upsertFromRow() со строкой, где engFuelType='плазма'.
     * 3. Ожидает ValidationException (валидация сырого значения через Rule::enum).
     */
    public function test_invalid_enum_throws_validation_exception(): void
    {
        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldNotReceive('upsertByEngId');

        $service = new UpsertEngineFromSheetService($command, new EngineDataFactory);

        $this->expectException(ValidationException::class);
        // несуществующий вид топлива — фабрика валидирует сырое значение через Rule::enum
        $service->upsertFromRow(new EngineSheetRowDTO(
            engId: 101,
            codeEngine: 'M54B30',
            engPowerKwStart: null,
            engPowerKwUpto: null,
            engPowerPsStart: null,
            engPowerPsUpto: null,
            engineCapacity: null,
            cylinderDiameter: null,
            cylinderCount: null,
            engNumberOfValves: null,
            engFuelType: 'плазма',
        ));
    }

    /**
     * Проверяет, что отсутствие обязательного идентификатора (eng_id) отклоняется
     * валидацией до вызова Command.
     *
     * Шаги:
     * 1. Мокает Command — ожидает, что upsertByEngId НЕ вызовется.
     * 2. Зовёт upsertFromRow() со строкой, где engId=null.
     * 3. Ожидает ValidationException.
     */
    public function test_missing_eng_id_throws_validation_exception(): void
    {
        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldNotReceive('upsertByEngId');

        $service = new UpsertEngineFromSheetService($command, new EngineDataFactory);

        $this->expectException(ValidationException::class);
        $service->upsertFromRow(new EngineSheetRowDTO(
            engId: null,
            codeEngine: 'M54B30',
            engPowerKwStart: null,
            engPowerKwUpto: null,
            engPowerPsStart: null,
            engPowerPsUpto: null,
            engineCapacity: null,
            cylinderDiameter: null,
            cylinderCount: null,
            engNumberOfValves: null,
            engFuelType: null,
        ));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
