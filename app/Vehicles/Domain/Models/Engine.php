<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use App\Vehicles\Domain\Enums\EngineFuelTypeEnum;
use App\Vehicles\Application\Observers\Engine\EngineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy(EngineObserver::class)]
class Engine extends Model
{
    protected $fillable = [
        'eng_id',
        'code_engine',
        'engine_capacity',
        'cylinder_count',
        'cylinder_diameter',
        'details',
        'eng_power_kw_start',
        'eng_power_kw_upto',
        'eng_power_ps_upto',
        'eng_power_ps_start',
        'eng_fuel_type',
        'eng_number_of_valves',
        'group_id',
    ];

    protected $casts = [
        'eng_fuel_type' => EngineFuelTypeEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

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
