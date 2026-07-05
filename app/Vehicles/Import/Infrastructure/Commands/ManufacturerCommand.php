<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Import\Domain\ModelData\Manufacturer\ManufacturerData;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use Illuminate\Support\Arr;

final readonly class ManufacturerCommand implements ManufacturerCommandInterface
{
    public function create(ManufacturerData $data): ManufacturerData
    {
        return ManufacturerData::from(
            Manufacturer::query()->create(Arr::except($data->toArray(), ['id'])),
        );
    }

    public function update(ManufacturerData $data): ManufacturerData
    {
        $manufacturer = Manufacturer::query()->findOrFail($data->id);
        $manufacturer->update(Arr::except($data->toArray(), ['id']));

        return ManufacturerData::from($manufacturer);
    }

    public function upsertByMfaId(ManufacturerData $data): ManufacturerData
    {
        return ManufacturerData::from(
            Manufacturer::query()->updateOrCreate(
                ['mfa_id' => $data->mfaId],
                Arr::except($data->toArray(), ['id']),
            ),
        );
    }

    public function firstOrCreateByName(string $name, int $mfaId): ManufacturerData
    {
        return ManufacturerData::from(
            Manufacturer::query()->firstOrCreate(
                ['name' => $name],
                ['mfa_id' => $mfaId],
            ),
        );
    }

    public function firstOrCreateByMfaId(int $mfaId, string $name): ManufacturerData
    {
        return ManufacturerData::from(
            Manufacturer::query()->firstOrCreate(
                ['mfa_id' => $mfaId],
                ['name' => $name],
            ),
        );
    }

    public function delete(ManufacturerData $data): bool
    {
        return (bool) Manufacturer::query()->whereKey($data->id)->delete();
    }
}
