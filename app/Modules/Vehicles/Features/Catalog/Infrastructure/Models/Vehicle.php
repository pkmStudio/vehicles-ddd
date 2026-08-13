<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Представляет Eloquent-модель таблицы автомобилей внутри фичи Catalog.
 */
class Vehicle extends AbstractModel
{
    protected $casts = [
        'type' => VehicleTypeEnum::class,
        'type_carcase' => CarcaseTypeEnum::class,
        'steering_type' => SteeringTypeEnum::class,
        'provider' => ProviderEnum::class,
    ];

    public $timestamps = false;

    /**
     * Возвращает производителя автомобиля.
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Возвращает родительскую модель/поколение автомобиля.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'parent_id', 'id');
    }

    /**
     * Возвращает дочерние модели/поколения автомобиля.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'parent_id', 'id');
    }

    /**
     * Возвращает модификации автомобиля.
     */
    public function modifications(): HasMany
    {
        return $this->hasMany(Modification::class);
    }

    /**
     * Возвращает спецификации деталей автомобиля.
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
     * - Вернуть значение enum для ТС как владельца спецификации.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::VEHICLE->value;
    }
}
