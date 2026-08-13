<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;

/**
 * Порт маппинга базовых vehicle-полей в Excel headings/cells.
 */
interface VehicleExportRowInterface
{
    /**
     * Возвращает базовые заголовки автомобиля без details-шаблона.
     *
     * Шаги:
     * 1) Собрать headings неизменяемых vehicle-полей.
     * 2) Вернуть headings в порядке Excel-колонок.
     *
     * @return array<int, string>
     */
    public function getBaseHeadings(): array;

    /**
     * Возвращает базовые ячейки автомобиля без details-шаблона.
     *
     * Шаги:
     * 1) Прочитать vehicle snapshot.
     * 2) Вернуть values в порядке базовых headings.
     *
     * @return array<int, mixed>
     */
    public function getBaseData(VehicleData $vehicle): array;
}
