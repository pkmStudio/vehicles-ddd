<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

class Vehicle extends AbstractModel
{
    protected $casts = [
        'type' => VehicleTypeEnum::class,
        'type_carcase' => CarcaseTypeEnum::class,
        'steering_type' => SteeringTypeEnum::class,
        'provider' => ProviderEnum::class,
    ];

    public $timestamps = false;

    public function getMorphClass(): string
    {
        return PartableTypeEnum::VEHICLE->value;
    }
}
