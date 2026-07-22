<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;

/**
 * Описывает порт чтения модификаций из каталога.
 */
interface ModificationRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок модификаций по внешнему идентификатору.
     */
    public function findByModIdAndType(int $modId, string $type): ?ModificationData;

    /**
     * Возвращает количество связанных записей, блокирующих удаление.
     */
    public function engineModificationCountByModIdAndType(int $modId, string $type): ?int;
}
