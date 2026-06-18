<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Contracts\Repositories;

use App\Vehicles\Domain\Models\Engine;
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
