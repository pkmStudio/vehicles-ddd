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

    /**
     * Создать manufacturer row через Eloquent.
     *
     * Шаги:
     * 1) Преобразовать ManufacturerData в массив writable fields.
     * 2) Исключить локальный id.
     * 3) Создать запись и вернуть ManufacturerData snapshot.
     */
    public function create(ManufacturerData $data): ManufacturerData
    {
        return ManufacturerData::from(
            Manufacturer::query()->create(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS)),
        );
    }

    /**
     * Обновить manufacturer row по mfa_id через Eloquent.
     *
     * Шаги:
     * 1) Найти manufacturer по внешнему mfa_id.
     * 2) Обновить writable fields из ManufacturerData.
     * 3) Refresh model и вернуть ManufacturerData snapshot.
     */
    public function updateByMfaId(ManufacturerData $data): ManufacturerData
    {
        $manufacturer = Manufacturer::query()->where('mfa_id', $data->mfaId)->firstOrFail();
        $manufacturer->update(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS));

        return ManufacturerData::from($manufacturer->refresh());
    }
}
