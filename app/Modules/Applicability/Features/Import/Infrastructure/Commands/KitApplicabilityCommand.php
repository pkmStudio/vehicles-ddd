<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Commands;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Import\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityCreated;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityUpdated;

/**
 * Записывает импортированные связи применяемости набора.
 */
final readonly class KitApplicabilityCommand implements KitApplicabilityCommandInterface
{
    /**
     * Сохраняет imported-связь набора с модификацией и публикует факт создания/изменения.
     *
     * Шаги:
     * 1. Ищет существующую связь kit/modification в `kit_applicabilities`.
     * 2. Определяет, нужно ли публиковать updated event из-за смены source/algorithm.
     * 3. Выполняет `updateOrCreate` с source `IMPORTED` и algorithm `MANUAL_XLSX`.
     * 4. Для существующей неизмененной связи завершает метод без события.
     * 5. Для измененной связи публикует `KitApplicabilityUpdated`.
     * 6. Для новой связи публикует `KitApplicabilityCreated`.
     */
    public function saveImportedModificationTarget(int $kitId, int $modificationId): void
    {
        $existing = KitApplicability::query()
            ->where('kit_id', $kitId)
            ->where('target_type', ApplicabilityTargetTypeEnum::MODIFICATION)
            ->where('target_id', $modificationId)
            ->first();

        $shouldDispatchUpdated = $existing !== null
            && (
                $existing->source !== ApplicabilitySourceEnum::IMPORTED
                || $existing->algorithm !== KitApplicabilityAlgorithmEnum::MANUAL_XLSX
            );

        $applicability = KitApplicability::query()->updateOrCreate(
            [
                'kit_id' => $kitId,
                'target_type' => ApplicabilityTargetTypeEnum::MODIFICATION,
                'target_id' => $modificationId,
            ],
            [
                'source' => ApplicabilitySourceEnum::IMPORTED,
                'algorithm' => KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
            ],
        );

        if (! $applicability->wasRecentlyCreated) {
            if ($shouldDispatchUpdated) {
                event(new KitApplicabilityUpdated(
                    kitId: $kitId,
                    targetType: ApplicabilityTargetTypeEnum::MODIFICATION,
                    targetId: $modificationId,
                    source: ApplicabilitySourceEnum::IMPORTED,
                    algorithm: KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
                ));
            }

            return;
        }

        event(new KitApplicabilityCreated(
            kitId: $kitId,
            targetType: ApplicabilityTargetTypeEnum::MODIFICATION,
            targetId: $modificationId,
            source: ApplicabilitySourceEnum::IMPORTED,
            algorithm: KitApplicabilityAlgorithmEnum::MANUAL_XLSX,
        ));
    }
}
