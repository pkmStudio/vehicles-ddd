<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Applicability\Features\Export\Domain\DTOs\ModificationKitApplicabilityRowDTO;
use App\Modules\Applicability\Features\Export\Domain\DTOs\VehicleKitApplicabilityRowDTO;
use Illuminate\Support\Collection;

interface KitApplicabilityExportRepositoryInterface
{
    /**
     * Читает строки применяемости комплектов к автомобилям для основного export-листа.
     *
     * Шаги:
     * 1. Отбирает применяемости, где целью является vehicle part specification.
     * 2. Подтягивает комплект, состав комплекта и автомобильные данные.
     * 3. Возвращает строки как `VehicleKitApplicabilityRowDTO`.
     *
     * @return Collection<int, VehicleKitApplicabilityRowDTO>
     */
    public function vehicleRows(): Collection;

    /**
     * Читает строки применяемости комплектов к модификациям для XLSX export.
     *
     * Шаги:
     * 1. Отбирает ручные импортированные связи с target type `MODIFICATION`.
     * 2. Подтягивает модификацию, чтобы вернуть внешние `ms_id` и `mod_id`.
     * 3. Возвращает строки как `ModificationKitApplicabilityRowDTO`.
     *
     * @return Collection<int, ModificationKitApplicabilityRowDTO>
     */
    public function modificationRows(): Collection;
}
