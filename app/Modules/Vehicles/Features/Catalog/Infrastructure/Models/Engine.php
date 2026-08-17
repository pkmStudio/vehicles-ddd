<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Представляет Eloquent-модель таблицы двигателей внутри фичи Catalog.
 */
class Engine extends AbstractModel
{
    protected $casts = [
        'fuel_type' => EngineFuelTypeEnum::class,
        'engine_capacity' => 'float',
        'details' => 'array',
        'provider' => ProviderEnum::class,
        'allow_change_fields' => 'array',
    ];

    public $timestamps = false;

    /**
     * Возвращает модификации двигателя через pivot table.
     */
    public function modifications(): BelongsToMany
    {
        return $this
            ->belongsToMany(Modification::class)
            ->withPivot(['eng_id', 'mod_id', 'type']);
    }

    /**
     * Возвращает спецификации деталей двигателя.
     */
    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }

    /**
     * Возвращает стабильный morph type вместо FQCN модели.
     *
     * Шаги:
     * - Не использовать имя Eloquent-класса как morph type.
     * - Вернуть значение enum для двигателя как владельца спецификации.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::ENGINE->value;
    }
}
