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
     * Шаги:
     * 1) Принять id Warehouse-типа из export adapter.
     * 2) Получить строки номенклатуры через read-порт.
     * 3) Вернуть коллекцию NomenclatureData.
     *
     * @return Collection<int, NomenclatureData>
     */
    public function getRows(int $typeId): Collection;

    /**
     * Возвращает заголовки листа номенклатуры с учётом detail-шаблона.
     *
     * Шаги:
     * 1) Определить detail-шаблон по id Warehouse-типа.
     * 2) Объединить базовые заголовки с detail-заголовками Templates.
     * 3) Вернуть полный порядок колонок листа.
     *
     * @return array<int, string>
     */
    public function getHeadings(int $typeId): array;

    /**
     * Преобразует одну номенклатуру в строку Excel.
     *
     * Шаги:
     * 1) Принять NomenclatureData из Excel adapter.
     * 2) Собрать базовые значения номенклатуры.
     * 3) Дорендерить details через Templates client и вернуть строку.
     *
     * @return array<int, mixed>
     */
    public function mapRow(NomenclatureData $row): array;

    /**
     * Возвращает название типа для имени Excel-листа.
     *
     * Шаги:
     * 1) Найти Warehouse-тип по id.
     * 2) Взять его название или fallback для отсутствующего типа.
     * 3) Вернуть строку для имени Excel-листа.
     */
    public function title(int $typeId): string;

    /**
     * Возвращает строки справочного листа выбранного типа.
     *
     * Шаги:
     * 1) Определить detail-шаблон по id Warehouse-типа.
     * 2) Получить справочные значения Templates для этого шаблона.
     * 3) Разложить группы справочников в строки Excel.
     *
     * @return Collection<int, array<int, mixed>>
     */
    public function getReferenceRows(int $typeId): Collection;

    /**
     * Возвращает заголовки справочного листа выбранного типа.
     *
     * Шаги:
     * 1) Определить detail-шаблон по id Warehouse-типа.
     * 2) Получить имена справочных групп.
     * 3) Вернуть заголовки справочного листа.
     *
     * @return array<int, string>
     */
    public function getReferenceHeadings(int $typeId): array;
}
