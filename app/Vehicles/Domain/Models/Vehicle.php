<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use App\Vehicles\Domain\Enums\CarcaseTypeEnum;
use App\Vehicles\Domain\Enums\SteeringTypeEnum;
use App\Vehicles\Domain\Enums\VehicleTypeEnum;
use App\Models\Warehouse\Kit;
use App\Vehicles\Application\Observers\Vehicle\VehicleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[ObservedBy(VehicleObserver::class)]
class Vehicle extends Model
{
    protected $fillable = [
        'parent_id',
        'ms_id',
        'model',
        'type',
        'type_carcase',
        'provider',
        'manufacturer_id',
        'mfa_id',
        'name',
        'generation',
        'generation_short',
        'generation_year_from',
        'generation_year_to',
        'is_allow',
        'is_changed',
        'localized_name',
        'excel_table_id',
        'steering_type',
        'details',
    ];

    protected $casts = [
        'type' => VehicleTypeEnum::class,
        'type_carcase' => CarcaseTypeEnum::class,
        'steering_type' => SteeringTypeEnum::class,
        'details' => 'array',
    ];


    public $timestamps = false;

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

    public function kits(): MorphToMany
    {
        return $this->morphToMany(Kit::class, 'applicabilitable', 'kit_applicabilitables');
    }

    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }
}
