<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\EngineModificationExportRowDTO;
use Illuminate\Support\Collection;

/**
 * Read port данных pivot engine_modification для Excel export.
 */
interface EngineModificationRepositoryInterface
{
    /**
     * Вернуть все связи модификаций и двигателей.
     *
     * Шаги:
     * 1) Прочитать pivot rows в Infrastructure.
     * 2) Вернуть строгие DTO без stdClass наружу.
     *
     * @return Collection<int, EngineModificationExportRowDTO>
     */
    public function all(): Collection;
}
