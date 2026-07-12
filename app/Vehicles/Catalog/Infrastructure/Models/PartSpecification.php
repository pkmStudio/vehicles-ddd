<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Templates\Domain\Enums\DetailTemplateEnum;

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
