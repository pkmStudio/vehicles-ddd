<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Commands;

use App\Vehicles\Catalog\Domain\ModelData\PartSpecificationData;

/**
 * Описывает порт записи спецификаций деталей в каталоге.
 */
interface PartSpecificationCommandInterface
{
    /**
     * Создает запись спецификации детали.
     */
    public function create(PartSpecificationData $data): PartSpecificationData;

    /**
     * Обновляет запись спецификации детали.
     */
    public function update(PartSpecificationData $data): PartSpecificationData;

    /**
     * Удаляет запись спецификации детали по id.
     */
    public function deleteById(int $id): void;
}
