<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Models;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;

class KitApplicability extends AbstractModel
{
    protected $casts = [
        'target_type' => ApplicabilityTargetTypeEnum::class,
        'source' => ApplicabilitySourceEnum::class,
        'algorithm' => KitApplicabilityAlgorithmEnum::class,
    ];
}
