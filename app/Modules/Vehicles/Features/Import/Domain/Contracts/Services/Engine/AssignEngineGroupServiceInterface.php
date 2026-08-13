<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\AssignEngineGroupResultDTO;

interface AssignEngineGroupServiceInterface
{
    /**
     * Назначить двигатель в группу по коду двигателя.
     *
     * Шаги:
     * 1) Найти двигатель по code_engine.
     * 2) Обновить group_id, если двигатель найден.
     * 3) Вернуть DTO с итогом операции.
     */
    public function assignGroup(string $code, int $groupId): AssignEngineGroupResultDTO;
}
