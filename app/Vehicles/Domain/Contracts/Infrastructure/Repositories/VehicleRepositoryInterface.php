<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Repositories;

use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

interface VehicleRepositoryInterface
{
    public function find(int $id): ?Vehicle;

    public function findOrFail(int $id): Vehicle;

    public function all(): Collection;

    public function firstByMsId(int $msId): ?Vehicle;

    /** Минимальный ms_id (для генерации отрицательных id новых ТС). 0 если таблица пуста. */
    public function minMsId(): int;

    /** Для основного листа экспорта (с маркой и родителем). */
    public function forMainSheet(bool $onlyAllowed): Collection;

    /** Для листа дворников (со спецификациями шаблона wiper). */
    public function forWiperSheet(bool $onlyAllowed): Collection;
}
