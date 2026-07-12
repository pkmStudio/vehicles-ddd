<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Копия для Export — только связи, которые реально нужны выгрузке (manufacturer, parent,
 * partSpecifications). Modification здесь не дублируется (Export её не читает), поэтому
 * relation на неё сюда не переносим.
 */
class Vehicle extends AbstractModel
{
    protected $casts = [
        'type' => VehicleTypeEnum::class,
        'type_carcase' => CarcaseTypeEnum::class,
        'steering_type' => SteeringTypeEnum::class,
        'provider' => ProviderEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

    /**
     * `partable_type`/полиморфные связи должны совпадать со значением, которое пишут
     * остальные фичи и Maintenance (см. PartableTypeEnum), иначе своя копия модели по фиче
     * не находила бы чужие/уже существующие строки (см. plan.md §11, п.9).
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::VEHICLE->value;
    }

    // RELATIONS
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'parent_id', 'id');
    }

    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }
}
