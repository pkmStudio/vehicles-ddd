<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
