<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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
     * Возвращает значение особенности спецификации.
     */
    public function featureValue(): BelongsTo
    {
        return $this->belongsTo(FeatureValue::class, 'feature_value_id', 'id');
    }

    /**
     * Возвращает особенность спецификации через значение особенности.
     */
    public function feature(): HasOneThrough
    {
        return $this->hasOneThrough(
            related: Feature::class,
            through: FeatureValue::class,
            firstKey: 'id',
            secondKey: 'id',
            localKey: 'feature_value_id',
            secondLocalKey: 'feature_id',
        );
    }

    /**
     * Возвращает стабильный morph type вместо FQCN модели.
     *
     * Шаги:
     * - Не использовать имя Eloquent-класса как morph type.
     * - Вернуть значение enum, ожидаемое partable-связями спецификаций.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::VEHICLE->value;
    }
}
