<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\Engine\EngineData;
use Illuminate\Support\Collection;

interface EngineRepositoryInterface
{
    public function find(int $id): ?EngineData;

    public function findOrFail(int $id): EngineData;

    /** @return Collection<int, EngineData> */
    public function all(): Collection;

    public function firstByEngId(int $engId): ?EngineData;

    public function firstByCodeEngine(string $code): ?EngineData;
}
