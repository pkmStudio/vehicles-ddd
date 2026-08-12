<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Services;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityReferenceServiceInterface;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use Illuminate\Support\Collection;

final readonly class VehicleKitApplicabilityReferenceService implements VehicleKitApplicabilityReferenceServiceInterface
{
    /**
     * Возвращает строки справочника типов кузова для export reference sheet.
     *
     * Шаги:
     * 1. Берет все cases shared enum-а типов кузова Vehicles.
     * 2. Преобразует каждое значение в одноячеечную строку reference sheet.
     * 3. Возвращает collection строк для Excel export.
     */
    public function carcaseTypeRows(): Collection
    {
        return collect(array_map(
            static fn (CarcaseTypeEnum $case): array => [$case->value],
            CarcaseTypeEnum::cases(),
        ));
    }
}
