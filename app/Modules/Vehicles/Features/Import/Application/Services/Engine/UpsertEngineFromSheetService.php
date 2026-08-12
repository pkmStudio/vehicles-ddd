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

    /**
     * Инициализирует порты сценария upsert двигателя.
     *
     * Шаги:
     * 1) Сохранить command записи двигателя.
     * 2) Сохранить factory валидации и сборки `EngineData`.
     * 3) Сохранить repository для проверки существующей записи.
     */
    public function __construct(
        private EngineCommandInterface $command,
        private EngineDataFactoryInterface $factory,
        private EngineRepositoryInterface $engines,
    ) {}

    /**
     * Создает или обновляет двигатель из строки import-листа.
     *
     * Шаги:
     * 1) Собрать raw row array из typed sheet DTO.
     * 2) Валидировать и преобразовать строку в `EngineData` через factory.
     * 3) Найти существующий двигатель по `eng_id`.
     * 4) Выполнить create или update через command.
     * 5) Опубликовать catalog mutation event о создании или обновлении.
     * 6) Вернуть сохраненный `EngineData`.
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(EngineSheetRowDTO $row): EngineData
    {
        $data = $this->factory->make([
            'eng_id' => $row->engId,
            'code_engine' => $row->codeEngine,
            'power_kw_start' => $row->powerKwStart,
            'power_kw_upto' => $row->powerKwUpto,
            'power_ps_start' => $row->powerPsStart,
            'power_ps_upto' => $row->powerPsUpto,
            'engine_capacity' => $row->engineCapacity,
            'cylinder_diameter' => $row->cylinderDiameter,
            'cylinder_count' => $row->cylinderCount,
            'number_of_valves' => $row->numberOfValves,
            'fuel_type' => $row->fuelType,
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
