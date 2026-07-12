<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Repositories;

use App\Vehicles\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Catalog\Domain\ModelData\EngineData;
use App\Vehicles\Catalog\Infrastructure\Models\Engine;
use App\Vehicles\Catalog\Infrastructure\Models\EngineModification;
use App\Vehicles\Catalog\Infrastructure\Models\PartSpecification;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

final readonly class EngineRepository implements EngineRepositoryInterface
{
    public function firstByEngId(int $engId): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('eng_id', $engId)->first());
    }

    public function deletionBlockersByEngId(int $engId): ?array
    {
        $engine = Engine::query()->where('eng_id', $engId)->first();
        if ($engine === null) {
            return null;
        }

        return [
            'engine_modifications_count' => EngineModification::query()->where('engine_id', $engine->id)->count(),
            'part_specifications_count' => PartSpecification::query()
                ->where('partable_type', PartableTypeEnum::ENGINE->value)
                ->where('partable_id', $engine->id)
                ->count(),
        ];
    }
}
