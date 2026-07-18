<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовая Eloquent-модель копий Warehouse Maintenance с открытым mass-assignment — разовые фиксы
 * пишут напрямую через Eloquent (см. ARCHITECTURE.md, исключение для Maintenance).
 */
abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
