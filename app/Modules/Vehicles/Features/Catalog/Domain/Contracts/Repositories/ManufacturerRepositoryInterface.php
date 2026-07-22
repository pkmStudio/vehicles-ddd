<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;

/**
 * Описывает порт чтения производителей из каталога.
 */
interface ManufacturerRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок производителей по внешнему идентификатору.
     */
    public function findByMfaId(int $mfaId): ?ManufacturerData;

    /**
     * Возвращает количество связанных записей, блокирующих удаление.
     */
    public function vehicleCountByMfaId(int $mfaId): ?int;
}
