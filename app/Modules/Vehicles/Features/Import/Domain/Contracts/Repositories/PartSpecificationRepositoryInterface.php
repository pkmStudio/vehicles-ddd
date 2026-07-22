<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

/**
 * Чтение PartSpecification (read-only).
 */
interface PartSpecificationRepositoryInterface
{
    /**
     * Спецификация по натуральному ключу upsert-операции импорта.
     */
    public function findByPartableTemplateAndFeatureValue(
        string $partableType,
        int $partableId,
        DetailTemplateEnum $template,
        ?int $featureValueId,
    ): ?PartSpecificationData;

    /**
     * Все спецификации ТС по шаблону и стороне дворника.
     *
     * @return Collection<int, PartSpecificationData>
     */
    public function forVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): Collection;

    /**
     * Точная спецификация ТС по шаблону, стороне и JSON details.
     */
    public function findByVehicleTemplateSideAndDetails(int $vehicleId, DetailTemplateEnum $template, string $side, array $details): ?PartSpecificationData;
}
