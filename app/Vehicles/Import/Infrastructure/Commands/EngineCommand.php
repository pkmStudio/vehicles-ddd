<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\ModelData\Engine\EngineData;
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

    public function create(EngineData $data): EngineData
    {
        return EngineData::from(
            Engine::query()->create(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS)),
        );
    }

    public function update(EngineData $data): EngineData
    {
        $engine = Engine::query()->findOrFail($data->id);
        $engine->update(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS));

        return EngineData::from($engine);
    }

    public function upsertByEngId(EngineData $data): EngineData
    {
        return EngineData::from(
            Engine::query()->updateOrCreate(
                ['eng_id' => $data->engId],
                Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS),
            ),
        );
    }

    public function updateEditableByEngId(int $engId, array $attributes): EngineData
    {
        return EngineData::from(
            Engine::query()->updateOrCreate(
                ['eng_id' => $engId],
                $attributes,
            ),
        );
    }

    public function setGroupId(EngineData $engine, int $groupId): void
    {
        Engine::query()->whereKey($engine->id)->update(['group_id' => $groupId]);
    }

    public function delete(EngineData $data): bool
    {
        return (bool) Engine::query()->whereKey($data->id)->delete();
    }
}
