<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineModificationData;

interface EngineModificationCommandInterface
{
    /**
     * Привязывает двигатель к модификации по бизнес-ключам (если оба найдены).
     *
     * Шаги:
     * 1) Найти modification и engine по ключам из DTO.
     * 2) Добавить связь без удаления существующих связей.
     */
    public function syncWithoutDetaching(EngineModificationData $data): void;

    /**
     * Синхронизирует желаемый набор двигателей для одной группы `mod_id + type`.
     *
     * Шаги:
     * 1) Найти modification по mod_id/type.
     * 2) Найти engines по списку eng_id.
     * 3) Заменить связи этой modification ровно на переданный набор.
     *
     * @param  array<int, int>  $engIds
     */
    public function syncDesiredStateByModIdAndType(int $modId, string $type, array $engIds): void;
}
