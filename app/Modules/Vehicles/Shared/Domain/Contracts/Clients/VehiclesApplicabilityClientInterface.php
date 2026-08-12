<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Contracts\Clients;

use App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO;
use Illuminate\Support\Collection;

interface VehiclesApplicabilityClientInterface
{
    /**
     * @return Collection<int, VehiclePartSpecificationForApplicabilityDTO>
     *
     * Шаги:
     * 1. Найти front wiper specifications по основной и дополнительной длине.
     * 2. Отфильтровать варианты по количеству дворников.
     * 3. Вернуть DTO, пригодные для расчета применяемости.
     */
    public function frontWiperSpecifications(int $lengthMain, ?int $lengthSecond, int $countWipers): Collection;

    /**
     * @return Collection<int, VehiclePartSpecificationForApplicabilityDTO>
     *
     * Шаги:
     * 1. Найти rear wiper specifications по длине заднего дворника.
     * 2. Отфильтровать варианты по количеству дворников.
     * 3. Вернуть DTO, пригодные для расчета применяемости.
     */
    public function rearWiperSpecifications(int $lengthMain, int $countWipers): Collection;

    /**
     * Разрешает внутренний id modification по внешним TecDoc identifiers.
     *
     * Шаги:
     * 1. Найти modification по паре ms_id/mod_id.
     * 2. Вернуть внутренний primary key для downstream applicability import.
     */
    public function resolveModificationIdByMsAndModId(int $msId, int $modId): int;
}
