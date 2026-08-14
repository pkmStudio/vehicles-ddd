<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Eloquent-модель модификации на export boundary.
 */
class Modification extends AbstractModel
{
    protected $casts = [
        'type' => VehicleTypeEnum::class,
        'brake_system_type' => BrakeSystemTypeEnum::class,
        'engine_type' => EngineTypeEnum::class,
        'gear_type' => GearTypeEnum::class,
        'drive_type' => DriveTypeEnum::class,
        'provider' => ProviderEnum::class,
        'allow_change_fields' => 'array',
    ];

    public $timestamps = false;
}
