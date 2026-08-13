<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Modification;
use Illuminate\Support\Arr;

final readonly class ModificationCommand implements ModificationCommandInterface
{
    /** Служебные поля ModificationData, не участвующие в этой операции записи. */
    private const array NON_WRITABLE_FIELDS = ['id', 'engines'];

    /**
     * Создать modification row через Eloquent.
     *
     * Шаги:
     * 1) Преобразовать ModificationData в массив writable fields.
     * 2) Исключить служебные fields id/engines.
     * 3) Создать запись и вернуть ModificationData snapshot.
     */
    public function create(ModificationData $data): ModificationData
    {
        return ModificationData::from(
            Modification::query()->create(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS)),
        );
    }

    /**
     * Обновить modification row по mod_id/type через Eloquent.
     *
     * Шаги:
     * 1) Найти modification по натуральному ключу mod_id/type.
     * 2) Обновить writable fields из ModificationData.
     * 3) Refresh model и вернуть ModificationData snapshot.
     */
    public function updateByModIdAndType(ModificationData $data): ModificationData
    {
        $modification = Modification::query()
            ->where('mod_id', $data->modId)
            ->where('type', $data->type->value)
            ->firstOrFail();

        $modification->update(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS));

        return ModificationData::from($modification->refresh());
    }
}
