<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;

interface UpsertEngineSparkPlugSpecServiceInterface
{
    /**
     * Создать или обновить specification свечей двигателя.
     *
     * Шаги:
     * 1) Найти двигатель по eng_id.
     * 2) Найти существующую spark plug specification.
     * 3) Создать или обновить specification и вернуть snapshot.
     */
    public function upsertByEngine(int $engId, array $details): ?PartSpecificationData;
}
