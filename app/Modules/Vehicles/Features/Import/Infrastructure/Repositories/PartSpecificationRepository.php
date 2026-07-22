<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

/**
 * `partable_type` хранит стабильный дискриминатор полиморфной связи (см. PartableTypeEnum) —
 * общий для всех фич и Maintenance, не зависит от того, чья копия модели сейчас используется.
 */
final readonly class PartSpecificationRepository implements PartSpecificationRepositoryInterface
{
    public function firstByPartableTemplateAndFeatureValue(
        string $partableType,
        int $partableId,
        DetailTemplateEnum $template,
        ?int $featureValueId,
    ): ?PartSpecificationData {
        $specification = PartSpecification::query()
            ->where('partable_type', $partableType)
            ->where('partable_id', $partableId)
            ->where('template', $template->value)
            ->where('feature_value_id', $featureValueId)
            ->first();

        return PartSpecificationData::optional($specification);
    }

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
