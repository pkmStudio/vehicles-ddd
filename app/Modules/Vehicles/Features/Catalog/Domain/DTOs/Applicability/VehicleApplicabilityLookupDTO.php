<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Applicability;

/**
 * Минимальный снимок ТС для расчёта применяемости.
 */
final readonly class VehicleApplicabilityLookupDTO
{
    /**
     * Хранит внутренний id, внешний ms_id и связь с родительским ТС.
     */
    public function __construct(
        public int $id,
        public int $msId,
        public ?int $parentId,
    ) {}
}
