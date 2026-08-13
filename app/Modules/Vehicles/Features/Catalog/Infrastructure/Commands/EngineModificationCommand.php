<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\EngineModification;
use Illuminate\Support\Facades\DB;

final readonly class EngineModificationCommand implements EngineModificationCommandInterface
{
    /**
     * Синхронизирует связи модификации с актуальным набором двигателей.
     *
     * Шаги:
     * - В транзакции вычислить id двигателей, которые должны остаться связанными.
     * - Удалить устаревшие связи выбранной модификации.
     * - Создать или обновить связи для переданных двигателей с внутренними id.
     *
     * @param  list<EngineData>  $engines
     */
    public function syncForModification(ModificationData $modification, array $engines): void
    {
        DB::transaction(function () use ($modification, $engines): void {
            $engineIds = array_values(array_filter(array_map(
                static fn (EngineData $engine): ?int => $engine->id === null ? null : (int) $engine->id,
                $engines,
            )));

            EngineModification::query()
                ->where('modification_id', $modification->id)
                ->when($engineIds !== [], fn ($query) => $query->whereNotIn('engine_id', $engineIds))
                ->delete();

            foreach ($engines as $engine) {
                if ($engine->id === null || $modification->id === null) {
                    continue;
                }

                EngineModification::query()->updateOrCreate(
                    [
                        'engine_id' => (int) $engine->id,
                        'modification_id' => (int) $modification->id,
                    ],
                    [
                        'eng_id' => $engine->engId,
                        'mod_id' => $modification->modId,
                        'type' => $modification->type->value,
                    ],
                );
            }
        });
    }

    /**
     * Удаляет связи двигателей и модификаций по внутренним id связей.
     *
     * Шаги:
     * - Пропустить пустой список id.
     * - В транзакции удалить найденные pivot-записи.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            EngineModification::query()->whereIn('id', $ids)->delete();
        });
    }
}
