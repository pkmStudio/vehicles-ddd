<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\Export\Domain\ModelData\PackDimensionData;
use Illuminate\Support\Collection;

/**
 * Порт подготовки строк и справочников экспорта упаковочных размеров.
 */
interface PackDimensionExportServiceInterface
{
    /**
     * @return Collection<int, PackDimensionData>
     */
    public function getRows(): Collection;

    /**
     * @return array<int, string>
     */
    public function getHeadings(): array;

    /**
     * @return array<int, mixed>
     */
    public function mapRow(PackDimensionData $row): array;

    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function getReferenceRows(): Collection;
}
