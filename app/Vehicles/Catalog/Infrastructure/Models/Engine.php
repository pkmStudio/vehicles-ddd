<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

class Engine extends AbstractModel
{
    protected $casts = [
        'eng_fuel_type' => EngineFuelTypeEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

    public function getMorphClass(): string
    {
        return PartableTypeEnum::ENGINE->value;
    }
}
