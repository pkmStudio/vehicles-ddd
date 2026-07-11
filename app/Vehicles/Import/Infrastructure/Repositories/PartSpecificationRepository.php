<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\PartSpecificationData;
use App\Vehicles\Import\Infrastructure\Models\PartSpecification;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

/**
 * `partable_type` хранит стабильный дискриминатор полиморфной связи (см. PartableTypeEnum) —
 * общий для всех фич и Maintenance, не зависит от того, чья копия модели сейчас используется.
 */
final readonly class PartSpecificationRepository implements PartSpecificationRepositoryInterface
{
    public function forVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): Collection
    {
        $specifications = PartSpecification::query()
            ->where('partable_type', PartableTypeEnum::VEHICLE->value)
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
            ->where('partable_type', PartableTypeEnum::VEHICLE->value)
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
