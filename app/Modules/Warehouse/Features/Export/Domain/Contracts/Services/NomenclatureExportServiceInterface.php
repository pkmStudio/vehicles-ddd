<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\Export\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

/**
 * Порт подготовки строк, заголовков и справочников Warehouse-номенклатуры.
 */
interface NomenclatureExportServiceInterface
{
    /**
     * Возвращает строки номенклатуры выбранного типа.
     *
     * @return Collection<int, NomenclatureData>
     */
    public function getRows(int $typeId): Collection;

    /**
     * Возвращает заголовки листа номенклатуры с учётом detail-шаблона.
     *
     * @return array<int, string>
     */
    public function getHeadings(int $typeId): array;

    /**
     * Преобразует одну номенклатуру в строку Excel.
     *
     * @return array<int, mixed>
     */
    public function mapRow(NomenclatureData $row): array;

    /**
     * Возвращает название типа для имени Excel-листа.
     */
    public function title(int $typeId): string;

    /**
     * Возвращает строки справочного листа выбранного типа.
     *
     * @return Collection<int, array<int, mixed>>
     */
    public function getReferenceRows(int $typeId): Collection;

    /**
     * Возвращает заголовки справочного листа выбранного типа.
     *
     * @return array<int, string>
     */
    public function getReferenceHeadings(int $typeId): array;
}
