<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Задает общий базовый класс Eloquent-моделей фичи Catalog.
 */
abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
