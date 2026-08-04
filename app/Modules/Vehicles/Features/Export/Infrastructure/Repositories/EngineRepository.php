<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\EngineExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Engine;
use Illuminate\Support\Collection;

final readonly class EngineRepository implements EngineRepositoryInterface
{
    public function forSheet(EngineExportSheetEnum $sheet): Collection
    {
        return match ($sheet) {
            EngineExportSheetEnum::Main => $this->mainSheet(),
            EngineExportSheetEnum::SparkPlug => $this->sparkPlugSheet(),
        };
    }

    private function mainSheet(): Collection
    {
        return EngineData::collect(Engine::query()->get(), Collection::class);
    }

    private function sparkPlugSheet(): Collection
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
