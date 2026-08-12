<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Modification;
use Illuminate\Support\Collection;

/**
 * Читает modification snapshots для import-сценариев Vehicles.
 */
final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    /**
     * Ищет модификацию по TecDoc `mod_id` и типу транспорта.
     *
     * Шаги:
     * 1) Отфильтровать modification-модель по `mod_id` и `type`.
     * 2) Если модель не найдена — вернуть null.
     * 3) Сконвертировать найденную модель в typed `ModificationData`.
     */
    public function findByModIdAndType(int $modId, string $type): ?ModificationData
    {
        $modification = Modification::query()
            ->where('mod_id', $modId)
            ->where('type', $type)
            ->first();

        return $modification === null ? null : ModificationData::from($modification);
    }

    /**
     * Ищет модификацию с привязанными двигателями по vehicle `ms_id` и `mod_id`.
     *
     * Шаги:
     * 1) Отфильтровать modification-модель по `ms_id` и `mod_id`.
     * 2) Ограничить выборку модификациями, у которых есть engines.
     * 3) Загрузить engines relation.
     * 4) Если модель не найдена — вернуть null.
     * 5) Сконвертировать модель в `ModificationData` с typed коллекцией `EngineData`.
     */
    public function findByMsIdAndModIdWithEngines(int $msId, int $modId): ?ModificationData
    {
        $modification = Modification::query()
            ->where('ms_id', $msId)
            ->where('mod_id', $modId)
            ->has('engines')
            ->with('engines')
            ->first();

        if ($modification === null) {
            return null;
        }

        return ModificationData::from([
            ...$modification->toArray(),
            'engines' => EngineData::collect($modification->engines, Collection::class),
        ]);
    }
}
