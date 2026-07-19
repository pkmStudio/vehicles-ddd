<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Manufacturer;
use Illuminate\Support\Arr;

final readonly class ManufacturerCommand implements ManufacturerCommandInterface
{
    private const array NON_WRITABLE_FIELDS = ['id'];

    public function upsertByMfaId(ManufacturerData $data): ManufacturerData
    {
        return ManufacturerData::from(
            Manufacturer::query()->updateOrCreate(
                ['mfa_id' => $data->mfaId],
                Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS),
            ),
        );
    }
}
