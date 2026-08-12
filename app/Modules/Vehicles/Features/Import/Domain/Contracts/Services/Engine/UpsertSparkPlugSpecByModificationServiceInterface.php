<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\ModificationSparkPlugResultDTO;

interface UpsertSparkPlugSpecByModificationServiceInterface
{
    /**
     * Создать или обновить spark plug specification для двигателей модификации.
     *
     * Шаги:
     * 1) Найти модификацию по ms_id/mod_id с привязанными engines.
     * 2) Применить details к каждому найденному engine.
     * 3) Вернуть DTO с результатом обработки модификации.
     */
    public function upsertByModification(int $msId, int $modId, array $details): ModificationSparkPlugResultDTO;
}
