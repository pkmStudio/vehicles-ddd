<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Manufacturer;
use Illuminate\Support\Collection;

/**
 * Читает производителей автомобилей для Excel-экспорта Vehicles.
 */
final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    /**
     * Возвращает всех производителей в стабильном порядке внешнего TecDoc id.
     *
     * @return Collection<int, ManufacturerData>
     */
    public function all(): Collection
    {
        return ManufacturerData::collect(
            Manufacturer::query()
                ->orderBy('mfa_id')
                ->get(),
            Collection::class,
        );
    }
}
