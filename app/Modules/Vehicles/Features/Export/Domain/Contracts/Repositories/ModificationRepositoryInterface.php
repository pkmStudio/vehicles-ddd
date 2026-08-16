<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\ModificationData;
use Illuminate\Support\Collection;

/**
 * Read port данных модификаций для Excel export.
 */
interface ModificationRepositoryInterface
{
    /**
     * Вернуть все модификации для manager export.
     *
     * Шаги:
     * 1) Выполнить read query внутри Infrastructure.
     * 2) Вернуть typed snapshots без Eloquent наружу.
     *
     * @return Collection<int, ModificationData>
     */
    public function all(): Collection;
}
