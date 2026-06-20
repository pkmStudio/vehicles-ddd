<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Domain\ModelData\Manufacturer\ManufacturerData;
use App\Vehicles\Domain\Models\Manufacturer;

final readonly class ManufacturerCommand implements ManufacturerCommandInterface
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

    public function firstOrCreateByName(string $name, int $mfaId): Manufacturer
    {
        return Manufacturer::query()->firstOrCreate(
            ['name' => $name],
            ['mfa_id' => $mfaId],
        );
    }

    public function firstOrCreateByMfaId(int $mfaId, string $name): Manufacturer
    {
        return Manufacturer::query()->firstOrCreate(
            ['mfa_id' => $mfaId],
            ['name' => $name],
        );
    }

    public function delete(Manufacturer $manufacturer): bool
    {
        return (bool) $manufacturer->delete();
    }
}
