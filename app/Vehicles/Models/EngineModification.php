<?php

declare(strict_types=1);

namespace App\Vehicles\Models;

use App\Vehicles\Enums\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Model;

class EngineModification extends Model
{
    protected $table = 'engine_modification';

    public $timestamps = false;

    protected $fillable = [
        'engine_id',
        'modification_id',
        'eng_id',
        'mod_id',
        'type',
    ];

    protected $casts = [
        'type' => VehicleTypeEnum::class,
    ];
}
