<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Repositories;

use App\Vehicles\Domain\Enums\DetailTemplateEnum;
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
     * Спецификация ТС по шаблону и стороне дворника (front/back), независимо от feature_value_id.
     * Сторона определяется наличием корневого JSON-ключа (`jsonb_exists`, PostgreSQL).
     */
    public function firstByVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): ?PartSpecification;
}
