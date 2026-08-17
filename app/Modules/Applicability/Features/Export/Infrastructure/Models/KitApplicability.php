<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Models;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitApplicability extends AbstractModel
{
    protected $casts = [
        'target_type' => ApplicabilityTargetTypeEnum::class,
        'source' => ApplicabilitySourceEnum::class,
        'algorithm' => KitApplicabilityAlgorithmEnum::class,
    ];

    /**
     * Возвращает комплект, для которого хранится применяемость.
     */
    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }

    /**
     * Возвращает vehicle part specification, если применяемость привязана к спецификации.
     */
    public function partSpecification(): BelongsTo
    {
        return $this->belongsTo(PartSpecification::class, 'target_id');
    }

    /**
     * Возвращает modification, если применяемость привязана к модификации.
     */
    public function modification(): BelongsTo
    {
        return $this->belongsTo(Modification::class, 'target_id');
    }
}
