<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\EngineModification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись двигателей через Eloquent-модель фичи Catalog.
 */
final readonly class EngineCommand implements EngineCommandInterface
{
    public function __construct(
        private EngineModificationCommandInterface $engineModifications,
        private PartSpecificationCommandInterface $partSpecifications,
    ) {}

    /**
     * Создает запись двигателей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(EngineData $data): EngineData
    {
        $createEngine = fn (): EngineData => EngineData::from(
            Engine::query()->create(Arr::except($data->toArray(), ['id'])),
        );

        return DB::transaction($createEngine);
    }

    /**
     * Обновляет запись двигателей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(EngineData $data): EngineData
    {
        return DB::transaction(function () use ($data): EngineData {
            $engine = Engine::query()->where('eng_id', $data->engId)->firstOrFail();
            $engine->fill(Arr::except($data->toArray(), ['id']));
            $engine->save();

            return EngineData::from($engine->refresh());
        });
    }

    /**
     * Удаляет запись двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись и зависимые записи внутри транзакции.
     */
    public function deleteByEngId(int $engId): void
    {
        DB::transaction(function () use ($engId): void {
            $engine = Engine::query()->where('eng_id', $engId)->first();
            if ($engine === null) {
                return;
            }

            $toIntegerId = fn (mixed $id): int => (int) $id;

            $engineModificationIds = EngineModification::query()
                ->where('engine_id', $engine->id)
                ->pluck('id')
                ->map($toIntegerId)
                ->all();
            $partSpecificationIds = PartSpecification::query()
                ->where('partable_type', PartableTypeEnum::ENGINE->value)
                ->where('partable_id', $engine->id)
                ->pluck('id')
                ->map($toIntegerId)
                ->all();

            $this->engineModifications->deleteByIds($engineModificationIds);
            $this->partSpecifications->deleteByIds($partSpecificationIds);
            $engine->delete();
        });
    }
}
