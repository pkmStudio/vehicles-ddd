<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Repositories;

use App\Vehicles\Catalog\Domain\ModelData\ModificationData;

/**
 * Описывает порт чтения модификаций из каталога.
 */
interface ModificationRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок модификаций по внешнему идентификатору.
     */
    public function firstByModIdAndType(int $modId, string $type): ?ModificationData;

    /**
     * Возвращает количество связанных записей, блокирующих удаление.
     */
    public function engineModificationCountByModIdAndType(int $modId, string $type): ?int;
}
