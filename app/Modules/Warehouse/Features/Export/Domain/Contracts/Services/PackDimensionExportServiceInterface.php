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
     * Возвращает упаковочные размеры для листа данных.
     *
     * Шаги:
     * 1) Получить упаковочные размеры через read-порт.
     * 2) Вернуть коллекцию PackDimensionData для Excel adapter.
     *
     * @return Collection<int, PackDimensionData>
     */
    public function getRows(): Collection;

    /**
     * Возвращает заголовки листа упаковочных размеров.
     *
     * Шаги:
     * 1) Зафиксировать порядок колонок упаковки.
     * 2) Вернуть заголовки для Laravel Excel WithHeadings.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array;

    /**
     * Преобразует упаковочный размер в строку Excel.
     *
     * Шаги:
     * 1) Принять PackDimensionData от Excel adapter.
     * 2) Разложить поля упаковки и связанного типа.
     * 3) Вернуть плоский массив значений.
     *
     * @return array<int, mixed>
     */
    public function mapRow(PackDimensionData $row): array;

    /**
     * Возвращает строки справочника типов.
     *
     * Шаги:
     * 1) Получить все Warehouse-типы через read-порт.
     * 2) Преобразовать типы в строки справочного листа.
     * 3) Вернуть коллекцию строк.
     *
     * @return Collection<int, array<int, mixed>>
     */
    public function getReferenceRows(): Collection;
}
