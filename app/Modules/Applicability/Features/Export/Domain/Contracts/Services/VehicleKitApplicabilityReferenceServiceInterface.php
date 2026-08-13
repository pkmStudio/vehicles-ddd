<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Services;

use Illuminate\Support\Collection;

interface VehicleKitApplicabilityReferenceServiceInterface
{
    /**
     * Возвращает справочник типов кузова для export workbook.
     *
     * Шаги:
     * 1. Берет локально допустимые значения кузова через infrastructure boundary.
     * 2. Преобразует каждое значение в строку справочного листа.
     *
     * @return Collection<int, array<int, string>>
     */
    public function carcaseTypeRows(): Collection;
}
