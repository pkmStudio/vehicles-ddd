<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use App\Models\Warehouse\Kit;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class PartSpecification extends Model
{
    protected $casts = [
        'template' => DetailTemplateEnum::class,
        'details' => 'array',
    ];

    protected $fillable = [
        'feature_value_id',
        'template',
        'partable_type',
        'partable_id',
        'name',
        'text',
        'details',
    ];


    // RELATIONS
    public function featureValue(): BelongsTo
    {
        return $this->belongsTo(FeatureValue::class, 'feature_value_id', 'id');
    }

    public function partable(): MorphTo
    {
        return $this->morphTo();
    }

    public function vehicle(): BelongsTo
    {
        return $this
            ->belongsTo(Vehicle::class, 'partable_id', 'id')
            ->where('part_specifications.partable_type', Vehicle::class);
    }

    public function kits(): MorphToMany
    {
        return $this->morphToMany(Kit::class, 'applicabilitable', 'kit_applicabilitables');
    }
}
