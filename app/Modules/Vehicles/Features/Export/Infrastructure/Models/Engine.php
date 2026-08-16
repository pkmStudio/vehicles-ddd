<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Копия для Export — только связь partSpecifications (нужна листу свечей зажигания).
 * Modification здесь не дублируется (Export её не читает).
 */
class Engine extends AbstractModel
{
    protected $casts = [
        'fuel_type' => EngineFuelTypeEnum::class,
        'details' => 'array',
        'provider' => ProviderEnum::class,
        'allow_change_fields' => 'array',
    ];

    public $timestamps = false;

    /**
     * См. Vehicle::getMorphClass() — тот же повод: стабильный дискриминатор `partable_type`
     * общий для всех фич и Maintenance.
     *
     * Шаги:
     * - Вернуть enum-значение engine для polymorphic discriminator.
     * - Оставить связь совместимой с rows, созданными другими Vehicles features.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::ENGINE->value;
    }

    // RELATIONS
    /**
     * Связь двигателя с part specifications, которые выгружаются отдельными sheets.
     *
     * Шаги:
     * - Описать polymorphic morphMany связь через partable.
     * - Вернуть query relation на export-копию PartSpecification.
     */
    public function partSpecifications(): MorphMany
    {
        return $this->morphMany(PartSpecification::class, 'partable');
    }
}
