<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Repositories;

use App\Warehouse\Catalog\Domain\DTOs\Brand\BrandDeletionBlockersDTO;
use App\Warehouse\Catalog\Domain\ModelData\BrandData;

/**
 * Порт чтения Warehouse-брендов для Catalog-мутаций.
 */
interface BrandRepositoryInterface
{
    /**
     * Возвращает бренд по id или null.
     */
    public function find(int $id): ?BrandData;

    /**
     * Возвращает первый бренд с таким именем или null.
     */
    public function firstByName(string $name): ?BrandData;

    /**
     * Проверяет, занято ли имя другим брендом.
     */
    public function nameExistsForAnother(string $name, int $id): bool;

    /**
     * Собирает зависимости, блокирующие удаление бренда.
     */
    public function deletionBlockers(int $id): ?BrandDeletionBlockersDTO;
}
