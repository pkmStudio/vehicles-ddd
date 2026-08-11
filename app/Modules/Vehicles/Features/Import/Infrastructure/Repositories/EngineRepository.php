<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;

final readonly class EngineRepository implements EngineRepositoryInterface
{
    public function findByEngId(int $engId): ?EngineData
    {
        return $this->findByColumn('eng_id', $engId);
    }

    public function findByCodeEngine(string $code): ?EngineData
    {
        return $this->findByColumn('code_engine', $code);
    }

    private function findByColumn(string $column, int|string $value): ?EngineData
    {
        return EngineData::optional(
            Engine::query()
                ->where($column, $value)
                ->first(),
        );
    }
}
