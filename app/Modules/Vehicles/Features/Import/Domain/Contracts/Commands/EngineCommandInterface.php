<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface EngineCommandInterface
{
    /**
     * Создать двигатель из import data.
     *
     * Шаги:
     * 1) Передать validated EngineData в write adapter.
     * 2) Вернуть snapshot созданной записи.
     */
    public function create(EngineData $data): EngineData;

    /**
     * Обновить двигатель из import data.
     *
     * Шаги:
     * 1) Найти существующую запись по eng_id из EngineData.
     * 2) Применить значения EngineData.
     * 3) Вернуть обновленный snapshot.
     */
    public function update(EngineData $data): EngineData;

    /**
     * Проставить группу двигателю. Принимает Data с обязательными id и groupId.
     *
     * Шаги:
     * 1) Найти двигатель по локальному id из EngineData.
     * 2) Обновить group_id значением из EngineData.
     * 3) Вернуть обновленный snapshot.
     */
    public function setGroupId(EngineData $data): EngineData;
}
