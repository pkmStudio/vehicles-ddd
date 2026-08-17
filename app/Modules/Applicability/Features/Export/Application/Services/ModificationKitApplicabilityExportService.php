<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Application\Services;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\ModificationKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ModificationKitApplicabilityRowDTO;
use Illuminate\Support\Collection;

final readonly class ModificationKitApplicabilityExportService implements ModificationKitApplicabilityExportServiceInterface
{
    /**
     * Подключает источник строк применяемости комплектов к модификациям.
     */
    public function __construct(
        private KitApplicabilityExportRepositoryInterface $repository,
    ) {}

    /**
     * Возвращает строки, которые должны уйти в XLSX-файл ручной применяемости.
     */
    public function getRows(): Collection
    {
        return $this->repository->modificationRows();
    }

    /**
     * Возвращает значения строки в порядке, который читает импорт `kit_applicability`.
     */
    public function mapRow(ModificationKitApplicabilityRowDTO $row): array
    {
        return [
            $row->msId,
            $row->modId,
            $row->kitId,
        ];
    }

    /**
     * Возвращает технические заголовки, совпадающие с полями импортного row mapper-а.
     */
    public function getHeadings(): array
    {
        return [
            'ms_id',
            'mod_id',
            'kit_id',
        ];
    }
}
