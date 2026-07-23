<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kit extends AbstractModel
{
    protected $casts = [
        'complement' => 'boolean',
        'is_sale_separately' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

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
