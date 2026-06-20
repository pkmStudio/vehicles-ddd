<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories;

use App\Vehicles\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
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

    public function firstWhere(string $column, mixed $value): ?PartSpecification
    {
        return PartSpecification::query()->where($column, $value)->first();
    }

    public function firstByVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): ?PartSpecification
    {
        return PartSpecification::query()
            ->where('partable_type', Vehicle::class)
            ->where('partable_id', $vehicleId)
            ->where('template', $template->value)
            ->whereRaw('jsonb_exists(details, ?)', [$side])
            ->first();
    }
}
