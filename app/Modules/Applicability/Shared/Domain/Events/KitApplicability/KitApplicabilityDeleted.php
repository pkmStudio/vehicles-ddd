<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Domain\Events\KitApplicability;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

final readonly class KitApplicabilityDeleted
{
    public function __construct(
        public int $kitId,
        public ApplicabilityTargetTypeEnum $targetType,
        public int $targetId,
    ) {}
}
