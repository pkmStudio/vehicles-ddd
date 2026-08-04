<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\EngineModification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись модификаций через Eloquent-модель фичи Catalog.
 */
final readonly class ModificationCommand implements ModificationCommandInterface
{
    public function __construct(
        private EngineModificationCommandInterface $engineModifications,
    ) {}

    /**
     * Создает запись модификаций.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(ModificationData $data): ModificationData
    {
        $createModification = fn (): ModificationData => ModificationData::from(
            Modification::query()->create(Arr::except($data->toArray(), ['id'])),
        );

        return DB::transaction($createModification);
    }

    /**
     * Обновляет запись модификаций.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(ModificationData $data): ModificationData
    {
        return DB::transaction(function () use ($data): ModificationData {
            $modification = Modification::query()
                ->where('mod_id', $data->modId)
                ->where('type', $data->type->value)
                ->firstOrFail();
            $modification->fill(Arr::except($data->toArray(), ['id']));
            $modification->save();

            return ModificationData::from($modification->refresh());
        });
    }

    /**
     * Удаляет запись модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись и зависимые записи внутри транзакции.
     */
    public function deleteByModIdAndType(int $modId, string $type): void
    {
        DB::transaction(function () use ($modId, $type): void {
            $modification = Modification::query()
                ->where('mod_id', $modId)
                ->where('type', $type)
                ->first();

            if ($modification === null) {
                return;
            }

            $this->deleteByIds([(int) $modification->id]);
        });
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            $toIntegerId = fn (mixed $id): int => (int) $id;

            $engineModificationIds = EngineModification::query()
                ->whereIn('modification_id', $ids)
                ->pluck('id')
                ->map($toIntegerId)
                ->all();

            $this->engineModifications->deleteByIds($engineModificationIds);
            Modification::query()->whereIn('id', $ids)->delete();
        });
    }
}
