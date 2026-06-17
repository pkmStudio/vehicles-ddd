<?php

declare(strict_types=1);

namespace App\Vehicles\Traits;

use App\Vehicles\DTOs\VehicleData;
use App\Vehicles\Enums\SteeringTypeEnum;
use App\Vehicles\Models\Manufacturer;
use App\Vehicles\Models\Vehicle;

/**
 * Базовая логика записи ТС из ручного листа.
 * Использующий класс обязан объявить свойства:
 *   private readonly \App\Vehicles\Commands\Contracts\VehicleCommandInterface $vehicleCommand;
 *   private readonly \App\Vehicles\Validators\VehicleValidator $vehicleValidator;
 */
trait HasVehicleImportBaseData
{
    private function getMinMsId(): int
    {
        $minId = Vehicle::query()->min('ms_id');

        return min((int) $minId, 0);
    }

    private function getMinMfaId(): int
    {
        $minId = Manufacturer::query()->min('mfa_id');

        return min((int) $minId, 0);
    }

    private function getManufacturerIds(int &$minMfaId, array $row): array
    {
        if (empty($row[1])) {
            $manufacturer = Manufacturer::query()->firstOrCreate(
                ['name' => $row[3]],
                ['mfa_id' => --$minMfaId],
            );
        } else {
            $manufacturer = Manufacturer::query()->firstOrCreate(
                ['mfa_id' => $row[1]],
                ['name' => $row[3]],
            );
        }

        return [$manufacturer->mfa_id, $manufacturer->id];
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function createOrUpdateVehicle(array $row): Vehicle
    {
        $minMfaId = $this->getMinMfaId();
        $minMsId = $this->getMinMsId();

        $parentId = isset($row[13])
            ? Vehicle::query()->where('ms_id', $row[13])->first()?->id
            : null;

        [$mfaId, $manufacturerId] = $this->getManufacturerIds($minMfaId, $row);
        $msId = $row[2] ?? --$minMsId;

        $valid = $this->vehicleValidator->validate([
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
        ]);

        return $this->vehicleCommand->upsertByMsId(new VehicleData(
            msId: (int) $valid['ms_id'],
            mfaId: (int) $valid['mfa_id'],
            manufacturerId: $manufacturerId,
            name: (string) $valid['name'],
            type: (string) $valid['type'],
            steeringType: $valid['steering_type'] ?? SteeringTypeEnum::LEFT->value,
            generation: $valid['generation'] ?? null,
            typeCarcase: $valid['type_carcase'] ?? null,
            generationYearFrom: isset($valid['generation_year_from']) ? (int) $valid['generation_year_from'] : null,
            generationYearTo: isset($valid['generation_year_to']) ? (int) $valid['generation_year_to'] : null,
            provider: $valid['provider'] ?? 'TD',
            parentId: $parentId,
            excelTableId: $valid['excel_table_id'] ?? null,
            localizedName: $valid['localized_name'] ?? null,
            generationShort: $valid['generation_short'] ?? null,
            isAllow: (bool) ($valid['is_allow'] ?? false),
        ));
    }
}
