<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Представляет Eloquent-модель таблицы спецификаций деталей внутри фичи Catalog.
 */
class PartSpecification extends AbstractModel
{
    protected $casts = [
        'partable_type' => PartableTypeEnum::class,
        'template' => DetailTemplateEnum::class,
        'details' => 'array',
    ];

    /**
     * Возвращает стабильный morph type вместо FQCN модели.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::VEHICLE->value;
    }
}
