<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends AbstractModel
{
    protected $casts = [
        'provider' => ProviderEnum::class,
    ];

    public $timestamps = false;

    // RELATIONS
    /**
     * Связь производителя с автомобилями import boundary.
     *
     * Шаги:
     * 1) Описать hasMany relation на import-копию Vehicle.
     * 2) Вернуть relation для eager-load/read queries.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
