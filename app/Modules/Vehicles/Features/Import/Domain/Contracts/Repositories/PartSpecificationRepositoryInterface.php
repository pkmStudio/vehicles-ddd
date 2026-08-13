<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use Illuminate\Support\Collection;

/**
 * Чтение PartSpecification (read-only).
 */
interface PartSpecificationRepositoryInterface
{
    /**
     * Спецификация по натуральному ключу upsert-операции импорта.
     *
     * Шаги:
     * 1) Выполнить read query по owner, template и feature value.
     * 2) Вернуть PartSpecificationData или null.
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
     * Шаги:
     * 1) Выполнить read query по vehicle id, template и details.side.
     * 2) Вернуть collection найденных specifications.
     *
     * @return Collection<int, PartSpecificationData>
     */
    public function forVehicleTemplateAndSide(int $vehicleId, DetailTemplateEnum $template, string $side): Collection;

    /**
     * Точная спецификация ТС по шаблону, стороне и JSON details.
     *
     * Шаги:
     * 1) Выполнить read query по vehicle id, template, side и details payload.
     * 2) Вернуть PartSpecificationData или null.
     */
    public function findByVehicleTemplateSideAndDetails(int $vehicleId, DetailTemplateEnum $template, string $side, array $details): ?PartSpecificationData;
}
