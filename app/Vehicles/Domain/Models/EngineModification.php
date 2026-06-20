<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use App\Vehicles\Domain\Enums\Vehicle\VehicleTypeEnum;

class EngineModification extends BaseModel
{
    protected $table = 'engine_modification';

    public $timestamps = false;

    protected $casts = [
        'type' => VehicleTypeEnum::class,
    ];
}
