<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;

/**
 * Порт записи упаковочного размера Warehouse.
 */
interface PackDimensionCommandInterface
{
    /**
     * Обновляет упаковочный размер из import data.
     *
     * Шаги:
     * 1) Найти упаковочный размер по id из DTO.
     * 2) Обновить тип и габаритные поля.
     * 3) Вернуть актуальный снимок PackDimensionData.
     */
    public function update(PackDimensionData $data): PackDimensionData;

    /**
     * Создаёт новый упаковочный размер.
     *
     * Шаги:
     * 1) Собрать поля упаковочного размера из DTO.
     * 2) Создать новую запись.
     * 3) Вернуть снимок созданной PackDimensionData.
     */
    public function create(PackDimensionData $data): PackDimensionData;
}
