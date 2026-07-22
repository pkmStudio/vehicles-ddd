<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\Events\Engine\EngineCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Engine\EngineUpdated;

/**
 * Use-case: создать/обновить двигатель из строки листа импорта.
 * Бизнес-логика одной строки: маппинг колонок → валидация+сборка (Factory) → запись (Command).
 * Персистентность — только через порт Command, прямого Eloquent в Application нет.
 */
final readonly class UpsertEngineFromSheetService implements UpsertEngineFromSheetServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-engine-import';

    public function __construct(
        private EngineCommandInterface $command,
        private EngineDataFactoryInterface $factory,
        private EngineRepositoryInterface $engines,
    ) {}

    /**
     * @throws ImportRowValidationException
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

        $existing = $this->engines->findByEngId($data->engId);
        $engine = $existing === null
            ? $this->command->create($data)
            : $this->command->updateByEngId($data);

        event($existing === null
            ? new EngineCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $engine->toArray())
            : new EngineUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $engine->toArray()));

        return $engine;
    }
}
