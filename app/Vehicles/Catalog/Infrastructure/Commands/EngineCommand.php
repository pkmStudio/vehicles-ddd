<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Commands;

use App\Vehicles\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Catalog\Domain\ModelData\EngineData;
use App\Vehicles\Catalog\Infrastructure\Models\Engine;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class EngineCommand implements EngineCommandInterface
{
    public function create(EngineData $data): EngineData
    {
        return DB::transaction(
            fn (): EngineData => EngineData::from(
                Engine::query()->create(Arr::except($data->toArray(), ['id'])),
            ),
        );
    }

    public function update(EngineData $data): EngineData
    {
        return DB::transaction(function () use ($data): EngineData {
            $engine = Engine::query()->where('eng_id', $data->engId)->firstOrFail();
            $engine->fill(Arr::except($data->toArray(), ['id']));
            $engine->save();

            return EngineData::from($engine->refresh());
        });
    }

    public function deleteByEngId(int $engId): void
    {
        DB::transaction(function () use ($engId): void {
            Engine::query()->where('eng_id', $engId)->delete();
        });
    }
}
