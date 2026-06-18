<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands\Manufacturer;

use App\Vehicles\Infrastructure\Commands\Manufacturer\ManufacturerCommandInterface;
use App\Vehicles\Application\DTOs\Manufacturer\ManufacturerData;
use App\Vehicles\Domain\Models\Manufacturer;

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
