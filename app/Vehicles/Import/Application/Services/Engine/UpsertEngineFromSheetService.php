<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Engine;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\EngineDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpsertEngineFromSheetServiceInterface;
use App\Vehicles\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Vehicles\Import\Domain\ModelData\EngineData;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: создать/обновить двигатель из строки листа импорта.
 * Бизнес-логика одной строки: маппинг колонок → валидация+сборка (Factory) → запись (Command).
 * Персистентность — только через порт Command, прямого Eloquent в Application нет.
 */
final readonly class UpsertEngineFromSheetService implements UpsertEngineFromSheetServiceInterface
{
    public function __construct(
        private EngineCommandInterface $command,
        private EngineDataFactoryInterface $factory,
    ) {}

    /**
     * @throws ValidationException
     */
    public function upsertFromRow(EngineSheetRowDTO $row): EngineData
    {
        $data = $this->factory->make([
            'eng_id' => $row->engId,
            'code_engine' => $row->codeEngine,
            'eng_power_kw_start' => $row->engPowerKwStart,
            'eng_power_kw_upto' => $row->engPowerKwUpto,
            'eng_power_ps_start' => $row->engPowerPsStart,
            'eng_power_ps_upto' => $row->engPowerPsUpto,
            'engine_capacity' => $row->engineCapacity,
            'cylinder_diameter' => $row->cylinderDiameter,
            'cylinder_count' => $row->cylinderCount,
            'eng_number_of_valves' => $row->engNumberOfValves,
            'eng_fuel_type' => $row->engFuelType,
        ]);

        return $this->command->upsertByEngId($data);
    }
}
