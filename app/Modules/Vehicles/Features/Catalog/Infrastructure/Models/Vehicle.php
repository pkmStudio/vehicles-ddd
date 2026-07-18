<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

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
     * Возвращает стабильный morph type вместо FQCN модели.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::VEHICLE->value;
    }
}
