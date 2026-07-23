<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Domain\Events\KitApplicability;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

/**
 * Фиксирует доменный факт изменения связи применяемости набора.
 */
final readonly class KitApplicabilityUpdated
{
    /**
     * Инициализирует immutable-снимок измененной связи применяемости.
     */
    public function __construct(
        public int $kitId,
        public ApplicabilityTargetTypeEnum $targetType,
        public int $targetId,
    ) {}
}
