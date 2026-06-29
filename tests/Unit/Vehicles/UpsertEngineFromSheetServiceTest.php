<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\Factories\Engine\EngineDataFactory;
use App\Vehicles\Application\Import\Services\Engine\UpsertEngineFromSheetService;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineCommandInterface;
use App\Vehicles\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Vehicles\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Domain\Models\Engine;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * Построчный сценарий импорта двигателя. Command мокается — БД не нужна.
 */
final class UpsertEngineFromSheetServiceTest extends TestCase
{
    public function test_maps_row_and_upserts_via_command(): void
    {
        $captured = null;
        $expected = new Engine;

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
        $result = $service->execute([101, 'M54B30', 170, null, 231, null, '2979', 3.0, 6, 24, 'бензин']);

        $this->assertSame($expected, $result);
        $this->assertSame(101, $captured->engId);
        $this->assertSame('M54B30', $captured->codeEngine);
        $this->assertSame(170, $captured->engPowerKwStart);
        $this->assertSame(6, $captured->cylinderCount);
        $this->assertSame(EngineFuelTypeEnum::PETROL->value, $captured->engFuelType);
    }

    public function test_invalid_enum_throws_validation_exception(): void
    {
        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldNotReceive('upsertByEngId');

        $service = new UpsertEngineFromSheetService($command, new EngineDataFactory);

        $this->expectException(ValidationException::class);
        // несуществующий вид топлива — фабрика валидирует сырое значение через Rule::enum
        $service->execute([101, 'M54B30', null, null, null, null, null, null, null, null, 'плазма']);
    }

    public function test_missing_eng_id_throws_validation_exception(): void
    {
        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldNotReceive('upsertByEngId');

        $service = new UpsertEngineFromSheetService($command, new EngineDataFactory);

        $this->expectException(ValidationException::class);
        $service->execute([null, 'M54B30']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
