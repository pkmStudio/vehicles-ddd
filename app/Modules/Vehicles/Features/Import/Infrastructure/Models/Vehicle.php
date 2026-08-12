<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vehicle extends AbstractModel
{
    protected $casts = [
        'type' => VehicleTypeEnum::class,
        'type_carcase' => CarcaseTypeEnum::class,
        'steering_type' => SteeringTypeEnum::class,
        'provider' => ProviderEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

    /**
     * `partable_type` — стабильный дискриминатор полиморфной связи, общий для всех фич и
     * Maintenance (см. plan.md §11, п.9). partSpecifications() ниже сейчас не вызывается в
     * Import, но без этого override при первом реальном использовании тихо писал/фильтровал
     * бы по чужой строке своей копии класса.
     *
     * Шаги:
     * 1) Вернуть enum-значение vehicle для polymorphic discriminator.
     * 2) Оставить связь совместимой с rows, созданными другими Vehicles features.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::VEHICLE->value;
    }

    // RELATIONS
    /**
     * Связь автомобиля с модификациями import boundary.
     *
     * Шаги:
     * 1) Описать hasMany relation на import-копию Modification.
     * 2) Вернуть relation для read queries.
     */
    public function modifications(): HasMany
    {
        return $this->hasMany(Modification::class);
    }

    /**
     * Связь автомобиля с производителем.
     *
     * Шаги:
     * 1) Описать belongsTo relation на import-копию Manufacturer.
     * 2) Вернуть relation для eager-load/read queries.
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Связь автомобиля с родительской моделью/поколением.
     *
     * Шаги:
     * 1) Описать belongsTo self-relation через parent_id.
     * 2) Вернуть relation на import-копию Vehicle.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'parent_id', 'id');
    }

    /**
     * Связь автомобиля с дочерними моделями/поколениями.
     *
     * Шаги:
     * 1) Описать hasMany self-relation через parent_id.
     * 2) Вернуть relation на import-копию Vehicle.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'parent_id', 'id');
    }

    /**
     * Связь автомобиля с part specifications.
     *
     * Шаги:
     * 1) Описать polymorphic morphMany связь через partable.
     * 2) Вернуть relation на import-копию PartSpecification.
     */
    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }
}
