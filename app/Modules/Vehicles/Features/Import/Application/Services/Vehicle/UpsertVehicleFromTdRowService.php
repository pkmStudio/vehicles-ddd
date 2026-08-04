<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\VehicleImportWritePolicyInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleImportWriteContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\VehicleImportSourceEnum;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;

/**
 * Use-case: создать/обновить ТС из строки авторитетного импорта (приведение к виду TD).
 * Производитель должен уже существовать (резолв по mfa_id) — иначе сценарий сигналит null,
 * адаптер отражает это в отчёте об ошибках.
 */
final readonly class UpsertVehicleFromTdRowService implements UpsertVehicleFromTdRowServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-vehicle-import';

    public function __construct(
        private VehicleCommandInterface $command,
        private VehicleDataFactoryInterface $factory,
        private ManufacturerRepositoryInterface $manufacturers,
        private VehicleRepositoryInterface $vehicles,
        private VehicleImportWritePolicyInterface $writePolicy,
    ) {}

    /**
     * @return VehicleData|null null, если производитель с таким mfa_id не найден
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(VehicleTdRowDTO $row): ?VehicleData
    {
        if ($row->mfaId === null) {
            return null;
        }

        $manufacturer = $this->manufacturers->findByMfaId($row->mfaId);

        if (! $manufacturer) {
            return null;
        }

        $type = $row->type;
        $typeCarcase = $row->typeCarcase;

        // TecDoc не даёт "Тип кузова" для мотоциклов — подставляем сами, иначе
        // NOT NULL constraint на vehicles.type_carcase падает сырым SQL-исключением.
        if (! $typeCarcase && $type === VehicleTypeEnum::MB->value) {
            $typeCarcase = CarcaseTypeEnum::MOTORCYCLE->value;
        }

        $data = $this->factory->make([
            'ms_id' => $row->msId,
            'mfa_id' => $row->mfaId,
            'name' => $row->name,
            'type' => $type,
            'type_carcase' => $typeCarcase,
            'generation' => $row->generation,
            'generation_year_from' => $row->generationYearFrom,
            'generation_year_to' => $row->generationYearTo,
            'manufacturer_id' => $manufacturer->id,
            'provider' => ProviderEnum::TD->value,
        ]);

        $existing = $this->vehicles->findByMsId($data->msId);
        $writeContext = new VehicleImportWriteContextDTO(
            source: VehicleImportSourceEnum::TecDocCommand,
            sourceProvider: ProviderEnum::TD,
            operationId: self::OPERATION_ID,
            msId: $data->msId,
            rowIdentifier: (string) $row->msId,
        );
        $writeData = $this->writePolicy->apply(
            incoming: $data,
            existing: $existing,
            context: $writeContext,
        );

        $vehicle = $existing === null
            ? $this->command->create($writeData)
            : $this->command->updateByMsId($writeData);

        event($existing === null
            ? new VehicleCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $vehicle->toArray())
            : new VehicleUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $vehicle->toArray()));

        return $vehicle;
    }
}
