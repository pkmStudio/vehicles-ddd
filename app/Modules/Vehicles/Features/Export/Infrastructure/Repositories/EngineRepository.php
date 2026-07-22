<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Engine;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

final readonly class EngineRepository implements EngineRepositoryInterface
{
    public function all(): Collection
    {
        return EngineData::collect(Engine::query()->get(), Collection::class);
    }

    public function forSparkPlugSheet(): Collection
    {
        $sparkPlugSpecifications = fn ($query) => $query->where('template', DetailTemplateEnum::SPARK_PLUGS);

        $engines = Engine::query()
            ->with([
                'partSpecifications' => $sparkPlugSpecifications,
            ])
            ->get();

        return EngineData::collect($engines, Collection::class);
    }
}
