<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Commands;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;
use App\Vehicles\Catalog\Infrastructure\Models\Manufacturer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class ManufacturerCommand implements ManufacturerCommandInterface
{
    public function create(ManufacturerData $data): ManufacturerData
    {
        return DB::transaction(
            fn (): ManufacturerData => ManufacturerData::from(
                Manufacturer::query()->create(Arr::except($data->toArray(), ['id'])),
            ),
        );
    }

    public function update(ManufacturerData $data): ManufacturerData
    {
        return DB::transaction(function () use ($data): ManufacturerData {
            $manufacturer = Manufacturer::query()->where('mfa_id', $data->mfaId)->firstOrFail();
            $manufacturer->fill(Arr::except($data->toArray(), ['id']));
            $manufacturer->save();

            return ManufacturerData::from($manufacturer->refresh());
        });
    }

    public function deleteByMfaId(int $mfaId): void
    {
        DB::transaction(function () use ($mfaId): void {
            Manufacturer::query()->where('mfa_id', $mfaId)->delete();
        });
    }
}
