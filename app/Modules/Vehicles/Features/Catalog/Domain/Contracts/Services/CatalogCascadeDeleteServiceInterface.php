<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services;

interface CatalogCascadeDeleteServiceInterface
{
    /**
     * Удаляет все ТС производителя вместе с зависимыми записями каталога.
     *
     * Шаги:
     * 1) Найти автомобили производителя.
     * 2) Удалить поддерево автомобилей, модификации, pivot-связи и specifications.
     */
    public function deleteVehiclesByManufacturerId(int $manufacturerId): void;

    /**
     * Удаляет указанные ТС вместе с их модификациями и спецификациями.
     *
     * Шаги:
     * - Принять внутренние id ТС, выбранные вызывающим сценарием.
     * - Удалить дочерние зависимости до удаления самих ТС.
     *
     * @param  array<int, int>  $vehicleIds
     */
    public function deleteVehiclesByIds(array $vehicleIds): void;

    /**
     * Удаляет зависимости модификаций без удаления самих модификаций.
     *
     * Шаги:
     * - Принять внутренние id модификаций.
     * - Удалить связи с двигателями и спецификации деталей этих модификаций.
     *
     * @param  array<int, int>  $modificationIds
     */
    public function deleteModificationDependencies(array $modificationIds): void;

    /**
     * Удаляет связи и спецификации, принадлежащие двигателю.
     *
     * Шаги:
     * 1) Найти pivot-связи двигателя с модификациями.
     * 2) Найти specifications, где двигатель является владельцем.
     * 3) Удалить найденные зависимости через command-порты.
     */
    public function deleteEngineDependencies(int $engineId): void;
}
