<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Domain\Events\KitApplicability;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

/**
 * Фиксирует доменный факт удаления связи применяемости набора.
 */
final readonly class KitApplicabilityDeleted
{
    /**
     * Инициализирует immutable-снимок удаленной связи применяемости.
     */
    public function __construct(
        public int $kitId,
        public ApplicabilityTargetTypeEnum $targetType,
        public int $targetId,
    ) {}
}
