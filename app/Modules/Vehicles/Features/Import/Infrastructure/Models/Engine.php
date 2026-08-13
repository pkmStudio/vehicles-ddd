<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Engine extends AbstractModel
{
    protected $casts = [
        'fuel_type' => EngineFuelTypeEnum::class,
        'details' => 'array',
    ];

    public $timestamps = false;

    /**
     * См. Vehicle::getMorphClass() — тот же повод.
     *
     * Шаги:
     * 1) Вернуть enum-значение engine для polymorphic discriminator.
     * 2) Оставить связь совместимой с rows, созданными другими Vehicles features.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::ENGINE->value;
    }

    // RELATIONS
    /**
     * Связь двигателя с модификациями через pivot table.
     *
     * Шаги:
     * 1) Описать belongsToMany relation на import-копию Modification.
     * 2) Добавить pivot fields, нужные для command import связи.
     */
    public function modifications(): BelongsToMany
    {
        return $this
            ->belongsToMany(Modification::class)
            ->withPivot(['eng_id', 'mod_id', 'type']);
    }

    /**
     * Связь двигателя с part specifications.
     *
     * Шаги:
     * 1) Описать polymorphic morphMany связь через partable.
     * 2) Вернуть relation на import-копию PartSpecification.
     */
    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }

    /**
     * Связь двигателя с другими двигателями той же группы.
     *
     * Шаги:
     * 1) Описать hasMany self-relation по group_id.
     * 2) Исключить текущую запись из результата.
     */
    public function relatedEngines(): HasMany
    {
        return $this->hasMany(Engine::class, 'group_id', 'group_id')
            ->where('id', '!=', $this->id);
    }
}
