<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
