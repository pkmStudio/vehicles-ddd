<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Копия для Maintenance — без relation на Engine: Engine-модель здесь не дублируется
 * (Maintenance её не читает).
 */
class Modification extends AbstractModel
{
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
}
