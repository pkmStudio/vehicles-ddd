<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Копия для Export — только связь partSpecifications (нужна листу свечей зажигания).
 * Modification здесь не дублируется (Export её не читает).
 */
class Engine extends AbstractModel
{
    protected $casts = [
        'eng_fuel_type' => EngineFuelTypeEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

    /**
     * См. Vehicle::getMorphClass() — тот же повод: стабильный дискриминатор `partable_type`
     * общий для всех фич и Maintenance.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::ENGINE->value;
    }

    // RELATIONS
    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }
}
