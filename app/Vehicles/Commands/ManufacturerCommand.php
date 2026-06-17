<?php

declare(strict_types=1);

namespace App\Vehicles\Commands;

use App\Vehicles\Commands\Contracts\ManufacturerCommandInterface;
use App\Vehicles\DTOs\ManufacturerData;
use App\Vehicles\Models\Manufacturer;

final class ManufacturerCommand implements ManufacturerCommandInterface
{
    public function create(ManufacturerData $data): Manufacturer
    {
        return Manufacturer::query()->create($data->toArray());
    }

    public function update(Manufacturer $manufacturer, ManufacturerData $data): Manufacturer
    {
        $manufacturer->update($data->toArray());

        return $manufacturer;
    }

    public function upsertByMfaId(ManufacturerData $data): Manufacturer
    {
        return Manufacturer::query()->updateOrCreate(
            ['mfa_id' => $data->mfaId],
            $data->toArray(),
        );
    }

    public function delete(Manufacturer $manufacturer): bool
    {
        return (bool) $manufacturer->delete();
    }
}
