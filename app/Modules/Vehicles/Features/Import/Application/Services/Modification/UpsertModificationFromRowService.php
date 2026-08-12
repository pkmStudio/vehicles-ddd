<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Modification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification\UpsertModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationUpdated;

/**
 * Use-case: создать/обновить модификацию из строки импорта (приведение к виду TD).
 * ТС должно уже существовать (резолв по ms_id) — иначе сценарий сигналит null,
 * адаптер отражает это в отчёте об ошибках.
 */
final readonly class UpsertModificationFromRowService implements UpsertModificationFromRowServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-modification-import';

    /**
     * Инициализирует порты сценария upsert модификации.
     *
     * Шаги:
     * 1) Сохранить command записи модификации.
     * 2) Сохранить factory валидации и сборки `ModificationData`.
     * 3) Сохранить repositories для vehicle lookup и проверки существующей модификации.
     */
    public function __construct(
        private ModificationCommandInterface $command,
        private ModificationDataFactoryInterface $factory,
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
    ) {}

    /**
     * Создает или обновляет модификацию из command import row.
     *
     * Шаги:
     * 1) Если в строке нет `ms_id` — вернуть null.
     * 2) Найти vehicle по `ms_id`; если он отсутствует — вернуть null.
     * 3) Собрать raw row array из typed command DTO и resolved vehicle id.
     * 4) Валидировать и преобразовать строку в `ModificationData`.
     * 5) Найти существующую модификацию по `mod_id` и type.
     * 6) Выполнить create или update через command.
     * 7) Опубликовать catalog mutation event о создании или обновлении.
     *
     * @return ModificationData|null null, если ТС с таким ms_id не найдено
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(ModificationCommandRowDTO $row): ?ModificationData
    {
        if ($row->msId === null) {
            return null;
        }

        $vehicle = $this->vehicles->findByMsId($row->msId);

        if (! $vehicle) {
            return null;
        }

        $data = $this->factory->make([
            'mod_id' => $row->modId,
            'type' => $row->type,
            'ms_id' => $row->msId,
            'year_from' => $row->yearFrom,
            'year_to' => $row->yearTo,
            'description' => $row->description,
            'power_ps' => $row->powerPs,
            'power_kw' => $row->powerKw,
            'engine_type' => $row->engineType,
            'gear_type' => $row->gearType,
            'drive_type' => $row->driveType,
            'brake_system_type' => $row->brakeSystemType,
            'number_of_cylinders' => $row->numberOfCylinders,
            'capacity_lt' => $row->capacityLt,
            'vehicle_id' => $vehicle->id,
        ]);

        $existing = $this->modifications->findByModIdAndType($data->modId, $data->type->value);
        $modification = $existing === null
            ? $this->command->create($data)
            : $this->command->updateByModIdAndType($data);

        event($existing === null
            ? new ModificationCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $modification->toArray())
            : new ModificationUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $modification->toArray()));

        return $modification;
    }
}
