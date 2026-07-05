<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Services\Modification;

use App\Vehicles\Domain\Contracts\Application\Import\Factories\ModificationDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Modification\UpsertModificationFromRowServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\ModificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Models\Modification;
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
     * @param  array<int, mixed>  $row
     * @return Modification|null null, если ТС с таким ms_id не найдено
     *
     * @throws ValidationException
     */
    public function upsertFromRow(array $row): ?Modification
    {
        $vehicle = $this->vehicles->firstByMsId((int) $row[0]);

        if (! $vehicle) {
            return null;
        }

        $data = $this->factory->make([
            'mod_id' => $row[1] ?? null,
            'type' => $row[13] ?? null,
            'ms_id' => $row[0] ?? null,
            'year_from' => $row[2] ?? null,
            'year_to' => $row[3] ?? null,
            'description' => $row[4] ?? null,
            'power_ps' => $row[5] ?? null,
            'power_kw' => $row[6] ?? null,
            'engine_type' => ($row[7] ?? null) ?: null,
            'gear_type' => ($row[8] ?? null) ?: null,
            'drive_type' => ($row[9] ?? null) ?: null,
            'brake_system_type' => ($row[10] ?? null) ?: null,
            'number_of_cylinders' => $row[11] ?? null,
            'capacity_lt' => $row[12] ?? null,
            'vehicle_id' => $vehicle->id,
        ]);

        return $this->command->upsertByModIdAndType($data);
    }
}
