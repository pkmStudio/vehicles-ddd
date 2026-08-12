<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kit extends AbstractModel
{
    /**
     * Связывает комплект с номенклатурами в порядке состава комплекта.
     */
    public function nomenclatures(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                related: Nomenclature::class,
                table: 'kit_nomenclature',
                foreignPivotKey: 'kit_id',
                relatedPivotKey: 'nomenclature_id',
            )
            ->withPivot('sort')
            ->orderBy('kit_nomenclature.sort');
    }
}
