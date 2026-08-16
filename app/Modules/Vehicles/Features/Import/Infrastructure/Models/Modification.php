<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Modification extends AbstractModel
{
    protected $casts = [
        'type' => VehicleTypeEnum::class,
        'brake_system_type' => BrakeSystemTypeEnum::class,
        'engine_type' => EngineTypeEnum::class,
        'gear_type' => GearTypeEnum::class,
        'drive_type' => DriveTypeEnum::class,
        'details' => 'array',
        'provider' => ProviderEnum::class,
        'allow_change_fields' => 'array',
    ];

    public $timestamps = false;

    // RELATIONS
    /**
     * Связь модификации с автомобилем.
     *
     * Шаги:
     * 1) Описать belongsTo relation на import-копию Vehicle.
     * 2) Вернуть relation для read/upsert queries.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Связь модификации с двигателями через pivot table.
     *
     * Шаги:
     * 1) Описать belongsToMany relation на import-копию Engine.
     * 2) Добавить pivot fields, нужные для engine-modification import.
     */
    public function engines(): BelongsToMany
    {
        return $this
            ->belongsToMany(Engine::class)
            ->withPivot(['eng_id', 'mod_id', 'type']);
    }
}
