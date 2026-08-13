<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Commands;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;

interface KitApplicabilityCommandInterface
{
    /**
     * Синхронизирует рассчитанные цели применяемости комплекта.
     *
     * Шаги:
     * 1. Удаляет устаревшие calculated targets комплекта для переданных target type и algorithm.
     * 2. Создает или обновляет актуальные target ids как calculated applicability rows.
     * 3. Публикует shared facts о создании, обновлении и удалении записей применяемости.
     *
     * @param  array<int, int>  $targetIds
     */
    public function syncCalculatedTargets(
        int $kitId,
        ApplicabilityTargetTypeEnum $targetType,
        KitApplicabilityAlgorithmEnum $algorithm,
        array $targetIds,
    ): void;
}
