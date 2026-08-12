<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\EngineExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Engine;
use Illuminate\Support\Collection;

/**
 * Читает двигатели и связанные specification records для Excel-экспорта Vehicles.
 */
final readonly class EngineRepository implements EngineRepositoryInterface
{
    /**
     * Возвращает данные двигателей для конкретного export-листа.
     *
     * Шаги:
     * 1) Сопоставить enum листа с приватным query method.
     * 2) Вернуть коллекцию typed `EngineData`.
     */
    public function forSheet(EngineExportSheetEnum $sheet): Collection
    {
        return match ($sheet) {
            EngineExportSheetEnum::Main => $this->mainSheet(),
            EngineExportSheetEnum::SparkPlug => $this->sparkPlugSheet(),
        };
    }

    /**
     * Возвращает данные для основного листа двигателей.
     *
     * Шаги:
     * 1) Прочитать все engine-модели.
     * 2) Сконвертировать Eloquent collection в Support Collection typed `EngineData`.
     */
    private function mainSheet(): Collection
    {
        return EngineData::collect(Engine::query()->get(), Collection::class);
    }

    /**
     * Возвращает двигатели со спецификациями свечей зажигания.
     *
     * Шаги:
     * 1) Подготовить scope для загрузки только `SPARK_PLUGS` part specifications.
     * 2) Прочитать engine-модели с отфильтрованной связью.
     * 3) Сконвертировать Eloquent collection в Support Collection typed `EngineData`.
     */
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
