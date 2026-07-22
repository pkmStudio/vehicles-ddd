<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Vehicle\VehicleUpdated;

/**
 * Use-case: создать/обновить ТС из строки ручного листа.
 * Оркестрация: резолв производителя → валидация → запись. Персистентность — только через порты
 * (Repository/Command), прямого Eloquent в Application нет.
 */
final readonly class UpsertVehicleFromSheetService implements UpsertVehicleFromSheetServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string MANUFACTURER_OPERATION_ID = 'vehicles-manufacturer-import';

    private const string VEHICLE_OPERATION_ID = 'vehicles-vehicle-import';

    public function __construct(
        private VehicleCommandInterface $command,
        private VehicleDataFactoryInterface $factory,
        private ManufacturerDataFactoryInterface $manufacturerFactory,
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
        private ManufacturerCommandInterface $manufacturerCommand,
    ) {}

    /**
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(VehicleSheetRowDTO $row): VehicleData
    {
        $minMfaId = min($this->manufacturers->minMfaId(), 0);
        $minMsId = min($this->vehicles->minMsId(), 0);

        $parentId = $row->parentMsId !== null
            ? $this->vehicles->findByMsId($row->parentMsId)?->id
            : null;

        $type = $row->type;
        $typeCarcase = $row->typeCarcase;

        // TODO: удалить после прогонки импорта и экспорта.
        // TecDoc не даёт тип кузова для мотоциклов — подставляем безопасный дефолт,
        // иначе падает валидатор VehicleDataFactory.
        if (! $typeCarcase && $type === VehicleTypeEnum::MB->value) {
            $typeCarcase = CarcaseTypeEnum::MOTORCYCLE->value;
        }

        [$mfaId, $manufacturerId] = $this->resolveManufacturer($minMfaId, $row);
        $msId = $row->msId ?? --$minMsId;

        $data = $this->factory->make([
            'ms_id' => $msId,
            'mfa_id' => $mfaId,
            'name' => $row->name,
            'type' => $row->type,
            'type_carcase' => $typeCarcase,
            'steering_type' => $row->steeringType,
            'generation' => $row->generation,
            'generation_short' => $row->generationShort,
            'localized_name' => $row->localizedName,
            'excel_table_id' => $row->excelTableId,
            'provider' => $row->provider,
            'generation_year_from' => $row->generationYearFrom,
            'generation_year_to' => $row->generationYearTo,
            'is_allow' => $row->isAllow,
            'manufacturer_id' => $manufacturerId,
            'parent_id' => $parentId,
        ]);

        $existing = $this->vehicles->findByMsId($data->msId);
        $vehicle = $existing === null
            ? $this->command->create($data)
            : $this->command->updateByMsId($data);

        event($existing === null
            ? new VehicleCreated(self::IMPORT_USER_ID, self::VEHICLE_OPERATION_ID, $vehicle->toArray())
            : new VehicleUpdated(self::IMPORT_USER_ID, self::VEHICLE_OPERATION_ID, $vehicle->toArray()));

        return $vehicle;
    }

    /**
     * @return array{0: int, 1: int} [mfa_id, manufacturer_id]
     */
    private function resolveManufacturer(int &$minMfaId, VehicleSheetRowDTO $row): array
    {
        $manufacturer = $row->mfaId === null
            ? $this->manufacturers->findByName((string) $row->manufacturerName)
            : $this->manufacturers->findByMfaId($row->mfaId);

        if (! $manufacturer) {
            $mfaId = $row->mfaId ?? --$minMfaId;

            $manufacturerData = $this->manufacturerFactory->make([
                'mfa_id' => $mfaId,
                'name' => $row->manufacturerName,
                'provider' => ProviderEnum::OD->value,
            ]);

            $manufacturer = $this->manufacturerCommand->create($manufacturerData);

            event(new ManufacturerCreated(
                self::IMPORT_USER_ID,
                self::MANUFACTURER_OPERATION_ID,
                $manufacturer->toArray(),
            ));
        }

        return [$manufacturer->mfaId, $manufacturer->id];
    }
}
