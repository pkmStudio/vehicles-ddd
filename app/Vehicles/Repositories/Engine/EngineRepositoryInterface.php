<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories\Engine;

use App\Vehicles\Models\Engine;
use Illuminate\Database\Eloquent\Collection;

interface EngineRepositoryInterface
{
    public function find(int $id): ?Engine;

    public function findOrFail(int $id): Engine;

    public function all(): Collection;

    public function firstByEngId(int $engId): ?Engine;

    /** Для листа свечей (со спецификациями шаблона sparkPlugs). */
    public function forSparkPlugSheet(): Collection;
}
