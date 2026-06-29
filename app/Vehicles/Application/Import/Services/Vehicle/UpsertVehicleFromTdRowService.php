<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Services\Vehicle;

use App\Vehicles\Domain\Contracts\Application\Import\Factories\VehicleDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\VehicleCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: создать/обновить ТС из строки авторитетного импорта (приведение к виду TD).
 * Производитель должен уже существовать (резолв по mfa_id) — иначе сценарий сигналит null,
 * адаптер отражает это в отчёте об ошибках.
 */
final readonly class UpsertVehicleFromTdRowService implements UpsertVehicleFromTdRowServiceInterface
{
    public function __construct(
        private VehicleCommandInterface $command,
        private VehicleDataFactoryInterface $factory,
        private ManufacturerRepositoryInterface $manufacturers,
    ) {}

    /**
     * @param  array<int, mixed>  $row
     * @return Vehicle|null null, если производитель с таким mfa_id не найден
     *
     * @throws ValidationException
     */
    public function execute(array $row): ?Vehicle
    {
        $manufacturer = $this->manufacturers->firstByMfaId((int) $row[0]);

        if (! $manufacturer) {
            return null;
        }

        $data = $this->factory->make([
            'ms_id' => $row[1] ?? null,
            'mfa_id' => $row[0] ?? null,
            'name' => $row[2] ?? null,
            'type' => $row[7] ?? null,
            'type_carcase' => ($row[4] ?? null) ?: null,
            'generation' => $row[3] ?? null,
            'generation_year_from' => $row[5] ?? null,
            'generation_year_to' => $row[6] ?? null,
            'manufacturer_id' => $manufacturer->id,
        ]);

        return $this->command->upsertByMsId($data);
    }
}
