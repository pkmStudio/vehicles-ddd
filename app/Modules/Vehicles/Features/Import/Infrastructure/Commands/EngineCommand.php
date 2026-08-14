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

    /**
     * Создать engine row через Eloquent.
     *
     * Шаги:
     * 1) Преобразовать EngineData в массив writable fields.
     * 2) Исключить служебные поля, которые не пишутся обычным create.
     * 3) Создать запись и вернуть EngineData snapshot.
     */
    public function create(EngineData $data): EngineData
    {
        return EngineData::from(
            Engine::query()->create(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS)),
        );
    }

    /**
     * Обновить engine row через Eloquent.
     *
     * Шаги:
     * 1) Найти engine по внешнему eng_id из EngineData.
     * 2) Обновить writable fields из уже подготовленного EngineData.
     * 3) Обновить writable fields и вернуть refresh snapshot.
     */
    public function update(EngineData $data): EngineData
    {
        $engine = Engine::query()->where('eng_id', $data->engId)->firstOrFail();
        $engine->update(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS));

        return EngineData::from($engine->refresh());
    }

    /**
     * Проставить group_id существующему engine.
     *
     * Шаги:
     * 1) Найти engine по локальному id из EngineData.
     * 2) Обновить только group_id.
     * 3) Перечитать model и вернуть EngineData snapshot.
     */
    public function setGroupId(EngineData $data): EngineData
    {
        Engine::query()->whereKey($data->id)->update(['group_id' => $data->groupId]);

        return EngineData::from(Engine::query()->findOrFail($data->id));
    }
}
