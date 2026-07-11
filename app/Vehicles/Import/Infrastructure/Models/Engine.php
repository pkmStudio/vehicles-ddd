<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Engine extends BaseModel
{
    protected $casts = [
        'eng_fuel_type' => EngineFuelTypeEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

    /** См. Vehicle::getMorphClass() — тот же повод. */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::ENGINE->value;
    }

    // RELATIONS
    public function modifications(): BelongsToMany
    {
        return $this
            ->belongsToMany(Modification::class)
            ->withPivot(['eng_id', 'mod_id', 'type']);
    }

    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }

    public function relatedEngines(): HasMany
    {
        return $this->hasMany(Engine::class, 'group_id', 'group_id')
            ->where('id', '!=', $this->id);
    }
}
