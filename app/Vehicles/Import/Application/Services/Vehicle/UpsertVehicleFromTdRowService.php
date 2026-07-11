<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Vehicle;

use App\Vehicles\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Vehicles\Import\Domain\ModelData\Vehicle\VehicleData;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
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
     * @return VehicleData|null null, если производитель с таким mfa_id не найден
     *
     * @throws ValidationException
     */
    public function upsertFromRow(array $row): ?VehicleData
    {
        $manufacturer = $this->manufacturers->firstByMfaId((int) $row[0]);

        if (! $manufacturer) {
            return null;
        }

        $type = $row[7] ?? null;
        $typeCarcase = ($row[4] ?? null) ?: null;

        // TecDoc не даёт "Тип кузова" для мотоциклов — подставляем сами, иначе
        // NOT NULL constraint на vehicles.type_carcase падает сырым SQL-исключением.
        if (! $typeCarcase && $type === VehicleTypeEnum::MB->value) {
            $typeCarcase = CarcaseTypeEnum::MOTORCYCLE->value;
        }

        $data = $this->factory->make([
            'ms_id' => $row[1] ?? null,
            'mfa_id' => $row[0] ?? null,
            'name' => $row[2] ?? null,
            'type' => $type,
            'type_carcase' => $typeCarcase,
            'generation' => $row[3] ?? null,
            'generation_year_from' => $row[5] ?? null,
            'generation_year_to' => $row[6] ?? null,
            'manufacturer_id' => $manufacturer->id,
            'provider' => ProviderEnum::TD->value,
        ]);

        return $this->command->upsertByMsId($data);
    }
}
