<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface ModificationCommandInterface
{
    /**
     * Создать модификацию из import data.
     *
     * Шаги:
     * 1) Передать validated ModificationData в write adapter.
     * 2) Вернуть snapshot созданной записи.
     */
    public function create(ModificationData $data): ModificationData;

    /**
     * Обновить модификацию из import data.
     *
     * Шаги:
     * 1) Найти существующую запись по mod_id и type из ModificationData.
     * 2) Применить значения ModificationData.
     * 3) Вернуть обновленный snapshot.
     */
    public function update(ModificationData $data): ModificationData;
}
