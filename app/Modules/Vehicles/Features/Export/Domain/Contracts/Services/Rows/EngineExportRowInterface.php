<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;

/**
 * Порт маппинга базовых engine-полей в Excel headings/cells.
 */
interface EngineExportRowInterface
{
    /**
     * Возвращает базовые заголовки двигателя без details-шаблона.
     *
     * Шаги:
     * 1) Собрать headings неизменяемых engine-полей.
     * 2) Вернуть headings в порядке Excel-колонок.
     *
     * @return array<int, string>
     */
    public function getBaseHeadings(): array;

    /**
     * Возвращает базовые ячейки двигателя без details-шаблона.
     *
     * Шаги:
     * 1) Прочитать engine snapshot.
     * 2) Вернуть values в порядке базовых headings.
     *
     * @return array<int, mixed>
     */
    public function getBaseData(EngineData $engine): array;
}
