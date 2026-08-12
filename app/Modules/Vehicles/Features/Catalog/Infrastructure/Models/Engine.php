<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Представляет Eloquent-модель таблицы двигателей внутри фичи Catalog.
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
     * Возвращает стабильный morph type вместо FQCN модели.
     *
     * Шаги:
     * - Не использовать имя Eloquent-класса как morph type.
     * - Вернуть значение enum для двигателя как владельца спецификации.
     */
    public function getMorphClass(): string
    {
        return PartableTypeEnum::ENGINE->value;
    }
}
