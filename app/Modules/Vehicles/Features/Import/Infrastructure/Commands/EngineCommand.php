<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;
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

    public function setGroupId(EngineData $data): EngineData
    {
        Engine::query()->whereKey($data->id)->update(['group_id' => $data->groupId]);

        return EngineData::from(Engine::query()->findOrFail($data->id));
    }
}
