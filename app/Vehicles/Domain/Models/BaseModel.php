<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовая модель домена Vehicles.
 *
 * guarded = [] (unguarded) — mass-assignment разрешён для всех колонок.
 * Это безопасно в нашем пайплайне: запись идёт только через типизированные Command,
 * которым на вход даётся ModelData->toArray() (фиксированный набор полей), а не сырой
 * пользовательский ввод. Если конкретной модели нужно защитить колонку — переопредели
 * $guarded в ней.
 */
abstract class BaseModel extends Model
{
    protected $guarded = [];
}
