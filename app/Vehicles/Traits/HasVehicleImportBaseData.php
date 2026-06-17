<?php

declare(strict_types=1);

namespace App\Vehicles\Traits;

use App\Vehicles\Enums\SteeringTypeEnum;
use App\Vehicles\Models\Manufacturer;
use App\Vehicles\Models\Vehicle;

trait HasVehicleImportBaseData
{
    private function getMinMsId(): int
    {
        $minId = Vehicle::query()->min('ms_id');

        return min($minId, 0);
    }

    private function getMinMfaId(): int
    {
        $minId = Manufacturer::query()->min('mfa_id');

        return min($minId, 0);
    }

    private function getManufacturerIds(int &$minMfaId, array $row): array
    {
        if (empty($row[1])) {
            $manufacturer = Manufacturer::query()->firstOrCreate(
                ['name' => $row[3]],
                ['mfa_id' => --$minMfaId],
            );
        } else {
            $mfaId = $row[1];
            $manufacturer = Manufacturer::query()->firstOrCreate(
                ['mfa_id' => $mfaId],
                ['name' => $row[3]],
            );
        }

        return [
            $manufacturer->mfa_id,
            $manufacturer->id,
        ];
    }

    protected function createOrUpdateVehicle(array $row): Vehicle
    {
        $minMfaId = $this->getMinMfaId();
        $minMsId = $this->getMinMsId();
        $parentId = isset($row[13])
            ? Vehicle::query()->where('ms_id', $row[13])->first()?->id
            : null;

        [$mfaId, $manufacturerId] = $this->getManufacturerIds($minMfaId, $row);
        $msId = $row[2] ?? --$minMsId;

        return Vehicle::query()->updateOrCreate([
            'ms_id' => $msId,
        ], [
            'excel_table_id' => $row[0] ?? null,
            'manufacturer_id' => $manufacturerId,
            'mfa_id' => $mfaId,
            'ms_id' => $msId,
            'name' => $row[4],
            'localized_name' => $row[5] ?? null,
            'generation_short' => $row[6] ?? null,
            'generation' => $row[7] ?? null,
            'generation_year_from' => $row[8] ?? null,
            'generation_year_to' => $row[9] ?? null,
            'type_carcase' => $row[10] ?? null,
            'type' => $row[11],
            'provider' => $row[12],
            'parent_id' => $parentId,
            'steering_type' => $row[14] ?? SteeringTypeEnum::LEFT->value,
            'is_allow' => $row[15] === 'Да',
        ]);
    }
}
