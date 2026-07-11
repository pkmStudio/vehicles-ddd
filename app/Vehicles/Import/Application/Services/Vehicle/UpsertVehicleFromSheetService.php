<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Vehicle;

use App\Vehicles\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Vehicles\Import\Domain\ModelData\Vehicle\VehicleData;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: создать/обновить ТС из строки ручного листа.
 * Оркестрация: резолв производителя → валидация → запись. Персистентность — только через порты
 * (Repository/Command), прямого Eloquent в Application нет.
 */
final readonly class UpsertVehicleFromSheetService implements UpsertVehicleFromSheetServiceInterface
{
    public function __construct(
        private VehicleCommandInterface $command,
        private VehicleDataFactoryInterface $factory,
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
        private ManufacturerCommandInterface $manufacturerCommand,
    ) {}

    /**
     * @throws ValidationException
     */
    public function upsertFromRow(VehicleSheetRowDTO $row): VehicleData
    {
        $minMfaId = min($this->manufacturers->minMfaId(), 0);
        $minMsId = min($this->vehicles->minMsId(), 0);

        $parentId = $row->parentMsId !== null
            ? $this->vehicles->firstByMsId($row->parentMsId)?->id
            : null;

        [$mfaId, $manufacturerId] = $this->resolveManufacturer($minMfaId, $row);
        $msId = $row->msId ?? --$minMsId;

        $data = $this->factory->make([
            'ms_id' => $msId,
            'mfa_id' => $mfaId,
            'name' => $row->name,
            'type' => $row->type,
            'type_carcase' => $row->typeCarcase,
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

        return $this->command->upsertByMsId($data);
    }

    /**
     * @return array{0: int, 1: int} [mfa_id, manufacturer_id]
     */
    private function resolveManufacturer(int &$minMfaId, VehicleSheetRowDTO $row): array
    {
        $manufacturer = $row->mfaId === null
            ? $this->manufacturerCommand->firstOrCreateByName($row->manufacturerName, --$minMfaId)
            : $this->manufacturerCommand->firstOrCreateByMfaId($row->mfaId, $row->manufacturerName);

        return [$manufacturer->mfaId, $manufacturer->id];
    }
}
