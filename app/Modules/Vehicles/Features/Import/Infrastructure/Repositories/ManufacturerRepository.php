<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Manufacturer;

/**
 * Читает manufacturer snapshots для import-сценариев Vehicles.
 */
final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    /**
     * Ищет производителя по имени.
     *
     * Шаги:
     * 1) Делегировать поиск общему column lookup.
     * 2) Вернуть typed `ManufacturerData` или null.
     */
    public function findByName(string $name): ?ManufacturerData
    {
        return $this->findByColumn('name', $name);
    }

    /**
     * Ищет производителя по TecDoc `mfa_id`.
     *
     * Шаги:
     * 1) Делегировать поиск общему column lookup.
     * 2) Вернуть typed `ManufacturerData` или null.
     */
    public function findByMfaId(int $mfaId): ?ManufacturerData
    {
        return $this->findByColumn('mfa_id', $mfaId);
    }

    /**
     * Возвращает производителя с минимальным `mfa_id`.
     *
     * Шаги:
     * 1) Отсортировать manufacturers по `mfa_id`.
     * 2) Взять первую запись.
     * 3) Сконвертировать найденную Eloquent-модель в optional `ManufacturerData`.
     */
    public function findMinMfaId(): ?ManufacturerData
    {
        return ManufacturerData::optional(
            Manufacturer::query()
                ->orderBy('mfa_id')
                ->first(),
        );
    }

    /**
     * Выполняет общий точечный lookup производителя по колонке.
     *
     * Шаги:
     * 1) Отфильтровать manufacturer-модель по указанной колонке и значению.
     * 2) Сконвертировать найденную Eloquent-модель в optional `ManufacturerData`.
     */
    private function findByColumn(string $column, int|string $value): ?ManufacturerData
    {
        return ManufacturerData::optional(
            Manufacturer::query()
                ->where($column, $value)
                ->first(),
        );
    }
}
