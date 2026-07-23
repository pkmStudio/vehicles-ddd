<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Models;

use App\Modules\Applicability\Features\Calculation\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitApplicability extends AbstractModel
{
    protected $casts = [
        'target_type' => ApplicabilityTargetTypeEnum::class,
        'source' => ApplicabilitySourceEnum::class,
        'algorithm' => KitApplicabilityAlgorithmEnum::class,
    ];

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }

    public function partSpecification(): BelongsTo
    {
        return $this->belongsTo(PartSpecification::class, 'target_id');
    }
}
