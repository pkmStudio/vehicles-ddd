<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories;

use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

final readonly class PartSpecificationRepository implements PartSpecificationRepositoryInterface
{
    public function find(int $id): ?PartSpecification
    {
        return PartSpecification::query()->find($id);
    }

    public function findOrFail(int $id): PartSpecification
    {
        return PartSpecification::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return PartSpecification::query()->get();
    }

    public function forVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): Collection
    {
        return PartSpecification::query()
            ->where('partable_type', Vehicle::class)
            ->where('partable_id', $vehicleId)
            ->where('template', $template->value)
            ->whereRaw('jsonb_exists(details, ?)', [$side])
            ->orderBy('id')
            ->get();
    }

    public function firstByVehicleTemplateSideAndDetails(
        int $vehicleId,
        DetailTemplateEnum $template,
        string $side,
        array $details,
    ): ?PartSpecification {
        return PartSpecification::query()
            ->where('partable_type', Vehicle::class)
            ->where('partable_id', $vehicleId)
            ->where('template', $template->value)
            ->whereRaw('jsonb_exists(details, ?)', [$side])
            ->whereRaw('details = CAST(? AS jsonb)', [
                json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->orderBy('id')
            ->first();
    }
}
