<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Models;

use App\Modules\Applicability\Features\Calculation\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

class KitApplicability extends AbstractModel
{
    protected $casts = [
        'target_type' => ApplicabilityTargetTypeEnum::class,
        'source' => ApplicabilitySourceEnum::class,
        'algorithm' => KitApplicabilityAlgorithmEnum::class,
    ];
}
