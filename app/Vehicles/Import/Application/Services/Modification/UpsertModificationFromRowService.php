<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Modification;

use App\Vehicles\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\ModificationDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Modification\UpsertModificationFromRowServiceInterface;
use App\Vehicles\Import\Domain\DTOs\Modification\ModificationCommandRowDTO;
use App\Vehicles\Import\Domain\ModelData\ModificationData;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: создать/обновить модификацию из строки импорта (приведение к виду TD).
 * ТС должно уже существовать (резолв по ms_id) — иначе сценарий сигналит null,
 * адаптер отражает это в отчёте об ошибках.
 */
final readonly class UpsertModificationFromRowService implements UpsertModificationFromRowServiceInterface
{
    public function __construct(
        private ModificationCommandInterface $command,
        private ModificationDataFactoryInterface $factory,
        private VehicleRepositoryInterface $vehicles,
    ) {}

    /**
     * @return ModificationData|null null, если ТС с таким ms_id не найдено
     *
     * @throws ValidationException
     */
    public function upsertFromRow(ModificationCommandRowDTO $row): ?ModificationData
    {
        if ($row->msId === null) {
            return null;
        }

        $vehicle = $this->vehicles->firstByMsId($row->msId);

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

        return $this->command->upsertByModIdAndType($data);
    }
}
