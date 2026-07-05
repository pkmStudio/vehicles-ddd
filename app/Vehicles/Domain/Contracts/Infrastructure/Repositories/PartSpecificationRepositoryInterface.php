<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Repositories;

use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\PartSpecification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Чтение PartSpecification (read-only).
 */
interface PartSpecificationRepositoryInterface
{
    public function find(int $id): ?PartSpecification;

    public function findOrFail(int $id): PartSpecification;

    public function all(): Collection;

    /**
     * Все спецификации ТС по шаблону и стороне дворника.
     */
    public function forVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): Collection;

    /**
     * Точная спецификация ТС по шаблону, стороне и JSON details.
     */
    public function firstByVehicleTemplateSideAndDetails(int $vehicleId, DetailTemplateEnum $template, string $side, array $details): ?PartSpecification;
}
