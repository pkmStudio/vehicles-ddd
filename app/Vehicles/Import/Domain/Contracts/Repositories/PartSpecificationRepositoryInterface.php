<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

/**
 * Чтение PartSpecification (read-only).
 */
interface PartSpecificationRepositoryInterface
{
    public function find(int $id): ?PartSpecificationData;

    public function findOrFail(int $id): PartSpecificationData;

    /** @return Collection<int, PartSpecificationData> */
    public function all(): Collection;

    /**
     * Все спецификации ТС по шаблону и стороне дворника.
     *
     * @return Collection<int, PartSpecificationData>
     */
    public function forVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): Collection;

    /**
     * Точная спецификация ТС по шаблону, стороне и JSON details.
     */
    public function firstByVehicleTemplateSideAndDetails(int $vehicleId, DetailTemplateEnum $template, string $side, array $details): ?PartSpecificationData;
}
