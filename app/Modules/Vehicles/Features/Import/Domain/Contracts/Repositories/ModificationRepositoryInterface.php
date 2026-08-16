<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\DuplicateModificationNaturalKeyDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use Illuminate\Support\Collection;

/**
 * Чтение Modification (read-only).
 */
interface ModificationRepositoryInterface
{
    /**
     * Модификация по натуральному ключу upsert-операции импорта.
     *
     * Шаги:
     * 1) Выполнить read query по mod_id и type.
     * 2) Вернуть ModificationData или null.
     */
    public function findByModIdAndType(int $modId, string $type): ?ModificationData;

    /**
     * Модификация по натуральному ключу (ms_id + mod_id), имеющая двигатели, с загруженными
     * engines (ModificationData::$engines заполнен).
     *
     * Шаги:
     * 1) Выполнить read query по ms_id и mod_id с фильтром наличия engines.
     * 2) Eager-load engine snapshots для результата.
     * 3) Вернуть ModificationData или null.
     */
    public function findByMsIdAndModIdWithEngines(int $msId, int $modId): ?ModificationData;

    /**
     * Модификация с минимальным mod_id для генерации отрицательных OD identifiers.
     *
     * Шаги:
     * 1) Отсортировать модификации по mod_id.
     * 2) Вернуть snapshot минимальной записи или null.
     */
    public function findMinModId(): ?ModificationData;

    /**
     * Проверяет дубли natural key `mod_id + type` перед импортом связей.
     *
     * Шаги:
     * 1) Сгруппировать modification rows по mod_id/type.
     * 2) Вернуть список ключей, у которых найдено больше одной записи.
     *
     * @return Collection<int, DuplicateModificationNaturalKeyDTO>
     */
    public function duplicateNaturalKeys(): Collection;
}
