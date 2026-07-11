<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\ModelData\EngineData;
use App\Vehicles\Import\Infrastructure\Models\Engine;
use Illuminate\Support\Arr;

final readonly class EngineCommand implements EngineCommandInterface
{
    /**
     * Служебные поля EngineData, не участвующие в этих операциях записи: id — идентификатор,
     * а не колонка для записи; group_id пишется только через setGroupId(), чтобы обычный
     * upsert/create/update по листу импорта не затирал ранее назначенную группу.
     */
    private const array NON_WRITABLE_FIELDS = ['id', 'group_id'];

    public function upsertByEngId(EngineData $data): EngineData
    {
        return EngineData::from(
            Engine::query()->updateOrCreate(
                ['eng_id' => $data->engId],
                Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS),
            ),
        );
    }

    public function setGroupId(EngineData $engine, int $groupId): void
    {
        Engine::query()->whereKey($engine->id)->update(['group_id' => $groupId]);
    }
}
