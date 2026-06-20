<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Vehicle;

use App\Vehicles\Application\Import\Factories\Vehicle\VehicleDataFactory;
use App\Vehicles\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: создать/обновить ТС из строки ручного листа.
 * Оркестрация: резолв производителя → валидация → запись. Персистентность — только через порты
 * (Repository/Command), прямого Eloquent в Application нет.
 */
final readonly class UpsertVehicleFromSheetUseCase implements \App\Vehicles\Domain\Contracts\Import\UseCases\Vehicle\UpsertVehicleFromSheetUseCaseInterface
{
    public function __construct(
        private VehicleCommandInterface $command,
        private VehicleDataFactory $factory,
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
        private ManufacturerCommandInterface $manufacturerCommand,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(array $row): Vehicle
    {
        $minMfaId = min($this->manufacturers->minMfaId(), 0);
        $minMsId = min($this->vehicles->minMsId(), 0);

        $parentId = isset($row[13])
            ? $this->vehicles->firstByMsId((int) $row[13])?->id
            : null;

        [$mfaId, $manufacturerId] = $this->resolveManufacturer($minMfaId, $row);
        $msId = $row[2] ?? --$minMsId;

        $data = $this->factory->make([
            'ms_id' => $msId,
            'mfa_id' => $mfaId,
            'name' => $row[4] ?? null,
            'type' => $row[11] ?? null,
            'type_carcase' => ($row[10] ?? null) ?: null,
            'steering_type' => ($row[14] ?? null) ?: null,
            'generation' => $row[7] ?? null,
            'generation_short' => $row[6] ?? null,
            'localized_name' => $row[5] ?? null,
            'excel_table_id' => $row[0] ?? null,
            'provider' => $row[12] ?? null,
            'generation_year_from' => $row[8] ?? null,
            'generation_year_to' => $row[9] ?? null,
            'is_allow' => ($row[15] ?? null) === 'Да',
            'manufacturer_id' => $manufacturerId,
            'parent_id' => $parentId,
        ]);

        return $this->command->upsertByMsId($data);
    }

    /**
     * @return array{0: int, 1: int} [mfa_id, manufacturer_id]
     */
    private function resolveManufacturer(int &$minMfaId, array $row): array
    {
        $manufacturer = empty($row[1])
            ? $this->manufacturerCommand->firstOrCreateByName($row[3], --$minMfaId)
            : $this->manufacturerCommand->firstOrCreateByMfaId((int) $row[1], $row[3]);

        return [$manufacturer->mfa_id, $manufacturer->id];
    }
}
