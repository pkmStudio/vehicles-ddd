<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Представляет Eloquent-модель таблицы производителей внутри фичи Catalog.
 */
class Manufacturer extends AbstractModel
{
    protected $casts = [
        'provider' => ProviderEnum::class,
    ];

    public $timestamps = false;
}
