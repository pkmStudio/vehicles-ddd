<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use App\Vehicles\Domain\Enums\BrakeSystemTypeEnum;
use App\Vehicles\Domain\Enums\DriveTypeEnum;
use App\Vehicles\Domain\Enums\EngineTypeEnum;
use App\Vehicles\Domain\Enums\GearTypeEnum;
use App\Vehicles\Domain\Enums\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Modification extends Model
{
    protected $fillable = [
        'vehicle_id',
        'mod_id',
        'year_from',
        'year_to',
        'description',
        'brake_system_type',
        'type',
        'details',
        'ms_id',
        'capacity_lt',
        'gear_type',
        'engine_type',
        'power_kw',
        'number_of_cylinders',
        'excel_table_id',
        'drive_type',
        'localized_name',
        'power_ps',
    ];

    protected $casts = [
        'type' => VehicleTypeEnum::class,
        'brake_system_type' => BrakeSystemTypeEnum::class,
        'engine_type' => EngineTypeEnum::class,
        'gear_type' => GearTypeEnum::class,
        'drive_type' => DriveTypeEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

    // RELATIONS
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function engines(): BelongsToMany
    {
        return $this
            ->belongsToMany(Engine::class)
            ->withPivot(['eng_id', 'mod_id', 'type']);
    }
}
