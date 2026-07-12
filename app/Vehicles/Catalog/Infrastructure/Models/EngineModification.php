<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

class EngineModification extends AbstractModel
{
    protected $table = 'engine_modification';

    protected $casts = [
        'type' => VehicleTypeEnum::class,
    ];

    public $timestamps = false;
}
