<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Models;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

class PartSpecification extends AbstractModel
{
    protected $casts = [
        'partable_type' => PartableTypeEnum::class,
        'template' => DetailTemplateEnum::class,
        'details' => 'array',
    ];
}
