<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Представляет Eloquent-модель таблицы двигателей внутри фичи Catalog.
 */
class Engine extends AbstractModel
{
    protected $casts = [
        'eng_fuel_type' => EngineFuelTypeEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

    /**
     * Возвращает стабильный morph type вместо FQCN модели.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::ENGINE->value;
    }
}
