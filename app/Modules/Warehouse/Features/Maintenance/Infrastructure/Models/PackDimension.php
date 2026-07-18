<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия упаковочного размера Warehouse для Maintenance — очистка неиспользуемых коробок.
 */
class PackDimension extends AbstractModel
{
    protected $casts = [
        'generated' => 'boolean',
    ];

    /**
     * Возвращает наборы, использующие эту упаковку — нужен для проверки "не используется никем".
     */
    public function kits(): HasMany
    {
        return $this->hasMany(
            related: Kit::class,
            foreignKey: 'pack_dimension_id',
        );
    }
}
