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
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::VEHICLE->value;
    }

    // RELATIONS
    public function modifications(): HasMany
    {
        return $this->hasMany(Modification::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'parent_id', 'id');
    }

    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }
}
