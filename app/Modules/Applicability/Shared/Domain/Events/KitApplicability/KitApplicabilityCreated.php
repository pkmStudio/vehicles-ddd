<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Domain\Events\KitApplicability;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

/**
 * Фиксирует доменный факт создания связи применяемости набора.
 */
final readonly class KitApplicabilityCreated
{
    /**
     * Инициализирует immutable-снимок созданной связи применяемости.
     */
    public function __construct(
        public int $kitId,
        public ApplicabilityTargetTypeEnum $targetType,
        public int $targetId,
    ) {}
}
