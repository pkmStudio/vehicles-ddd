<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\EngineModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\EngineModificationExportRowDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Читает pivot engine_modification для Excel export.
 */
final readonly class EngineModificationRepository implements EngineModificationRepositoryInterface
{
    /**
     * Возвращает все связи `mod_id + eng_id + type`.
     *
     * Шаги:
     * 1) Прочитать pivot table в стабильном порядке.
     * 2) Сконвертировать database rows в DTO внутри repository.
     *
     * @return Collection<int, EngineModificationExportRowDTO>
     */
    public function all(): Collection
    {
        return DB::table('engine_modification')
            ->select('mod_id', 'eng_id', 'type')
            ->orderBy('mod_id')
            ->orderBy('type')
            ->orderBy('eng_id')
            ->get()
            ->map(static fn (stdClass $row): EngineModificationExportRowDTO => new EngineModificationExportRowDTO(
                modId: (int) $row->mod_id,
                engId: (int) $row->eng_id,
                type: (string) $row->type,
            ));
    }
}
