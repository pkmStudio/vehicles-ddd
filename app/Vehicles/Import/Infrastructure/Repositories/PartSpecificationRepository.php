<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Domain\Models\Vehicle as PartableVehicleType;
use App\Vehicles\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Import\Infrastructure\Models\PartSpecification;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

/**
 * `partable_type` хранит буквальное имя PHP-класса как дискриминатор полиморфной связи.
 * Vehicle/Engine дублируются по фичам (Import/Export/Maintenance держат свои копии моделей),
 * поэтому здесь используется общий App\Vehicles\Domain\Models\Vehicle::class как стабильное
 * значение колонки — то же самое, что уже хранится в БД и что пишет/читает Maintenance.
 * Использовать *::class копии моделей конкретной фичи для этой колонки нельзя: разные фичи
 * получили бы разные строки для одной и той же сущности.
 */
final readonly class PartSpecificationRepository implements PartSpecificationRepositoryInterface
{
    public function find(int $id): ?PartSpecificationData
    {
        return PartSpecificationData::optional(PartSpecification::query()->find($id));
    }

    public function findOrFail(int $id): PartSpecificationData
    {
        return PartSpecificationData::from(PartSpecification::query()->findOrFail($id));
    }

    public function all(): Collection
    {
        return PartSpecificationData::collect(PartSpecification::query()->get(), Collection::class);
    }

    public function forVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): Collection
    {
        $specifications = PartSpecification::query()
            ->where('partable_type', PartableVehicleType::class)
            ->where('partable_id', $vehicleId)
            ->where('template', $template->value)
            ->whereRaw('jsonb_exists(details, ?)', [$side])
            ->orderBy('id')
            ->get();

        return PartSpecificationData::collect($specifications, Collection::class);
    }

    public function firstByVehicleTemplateSideAndDetails(
        int $vehicleId,
        DetailTemplateEnum $template,
        string $side,
        array $details,
    ): ?PartSpecificationData {
        $specification = PartSpecification::query()
            ->where('partable_type', PartableVehicleType::class)
            ->where('partable_id', $vehicleId)
            ->where('template', $template->value)
            ->whereRaw('jsonb_exists(details, ?)', [$side])
            ->whereRaw('details = CAST(? AS jsonb)', [
                json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->orderBy('id')
            ->first();

        return PartSpecificationData::optional($specification);
    }
}
