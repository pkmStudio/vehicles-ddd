<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Domain\ModelData\KitData;
use Illuminate\Support\Collection;

/**
 * Порт подготовки строк и заголовков Excel-листа Warehouse-наборов.
 */
interface KitExportServiceInterface
{
    /**
     * Возвращает строки наборов для Excel-адаптера.
     *
     * Шаги:
     * 1) Принять фильтры и сортировку экспортного запроса.
     * 2) Получить подходящие наборы через read-порт.
     * 3) Вернуть коллекцию KitData для Excel collection().
     *
     * @return Collection<int, KitData>
     */
    public function getRows(KitExportFiltersDTO $filters, KitExportSortDTO $sort): Collection;

    /**
     * Возвращает заголовки листа наборов.
     *
     * Шаги:
     * 1) Получить порядок колонок из row-сервиса.
     * 2) Вернуть заголовки без доступа к инфраструктуре Excel.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array;

    /**
     * Преобразует набор в строку Excel.
     *
     * Шаги:
     * 1) Принять KitData из Excel-адаптера.
     * 2) Делегировать сборку значений row-сервису.
     * 3) Вернуть массив в порядке заголовков.
     *
     * @return array<int, mixed>
     */
    public function mapRow(KitData $row): array;
}
