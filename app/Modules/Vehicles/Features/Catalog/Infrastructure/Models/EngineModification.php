<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Представляет Eloquent-модель связи двигателя и модификации внутри фичи Catalog.
 */
class EngineModification extends AbstractModel
{
    protected $table = 'engine_modification';

    protected $casts = [
        'type' => VehicleTypeEnum::class,
    ];

    public $timestamps = false;
}
