<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use Illuminate\Support\Collection;

/**
 * Описывает порт чтения производителей из каталога.
 */
interface ManufacturerRepositoryInterface
{
    /**
     * Возвращает производителя по внутреннему идентификатору.
     *
     * Шаги:
     * 1. Принять внутренний id производителя.
     * 2. Вернуть `ManufacturerData` или `null`, если запись не найдена.
     */
    public function findById(int $id): ?ManufacturerData;

    /**
     * Возвращает первый Data-снимок производителей по внешнему идентификатору.
     *
     * Шаги:
     * 1. Принять внешний `mfa_id` производителя.
     * 2. Вернуть первый `ManufacturerData` или `null`, если запись не найдена.
     */
    public function findByMfaId(int $mfaId): ?ManufacturerData;

    /**
     * Возвращает производителей, у которых есть разрешённые ТС.
     *
     * Шаги:
     * 1. Найти производителей, связанных хотя бы с одним разрешенным автомобилем.
     * 2. Вернуть collection `ManufacturerData`.
     *
     * @return Collection<int, ManufacturerData>
     */
    public function findAllWithAllowedVehicles(): Collection;
}
