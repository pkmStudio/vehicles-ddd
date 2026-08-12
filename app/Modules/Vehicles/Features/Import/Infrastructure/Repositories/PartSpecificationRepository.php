<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Support\Collection;

/**
 * `partable_type` хранит стабильный дискриминатор полиморфной связи (см. PartableTypeEnum) —
 * общий для всех фич и Maintenance, не зависит от того, чья копия модели сейчас используется.
 */
final readonly class PartSpecificationRepository implements PartSpecificationRepositoryInterface
{
    /**
     * Ищет specification по владельцу, шаблону и feature value.
     *
     * Шаги:
     * 1) Отфильтровать `part_specifications` по polymorphic owner.
     * 2) Ограничить запись template enum value.
     * 3) Ограничить запись optional `feature_value_id`.
     * 4) Сконвертировать найденную Eloquent-модель в optional `PartSpecificationData`.
     */
    public function findByPartableTemplateAndFeatureValue(
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

    /**
     * Возвращает vehicle specifications конкретного шаблона, где details содержит сторону дворников.
     *
     * Шаги:
     * 1) Ограничить записи владельцем vehicle и переданным vehicle id.
     * 2) Ограничить записи template enum value.
     * 3) Проверить наличие JSONB-ключа стороны в details.
     * 4) Отсортировать записи по id для стабильного import/update порядка.
     * 5) Сконвертировать Eloquent collection в Support Collection typed `PartSpecificationData`.
     */
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

    /**
     * Ищет vehicle specification по template, стороне и полному details JSON.
     *
     * Шаги:
     * 1) Ограничить записи владельцем vehicle и переданным vehicle id.
     * 2) Ограничить записи template enum value.
     * 3) Проверить наличие JSONB-ключа стороны в details.
     * 4) Сравнить details с переданным JSON payload.
     * 5) Вернуть первую запись в стабильном порядке id как optional `PartSpecificationData`.
     *
     * @param  array<string, mixed>  $details
     */
    public function findByVehicleTemplateSideAndDetails(
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
