<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogManufacturerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use Illuminate\Support\Collection;

/**
 * Возвращает REST-список производителей публичного каталога.
 */
final readonly class ListManufacturersForCatalogUseCase
{
    /**
     * Подключает репозиторий производителей публичного каталога.
     *
     * Шаги:
     * - Сохранить зависимость для последующего чтения производителей с разрешёнными ТС.
     */
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
    ) {}

    /**
     * Возвращает производителей, у которых есть разрешённые к показу ТС.
     *
     * Шаги:
     * - Запросить производителей через catalog-read репозиторий.
     * - Преобразовать каждую запись в DTO для публичного каталога.
     * - Сбросить ключи коллекции перед возвратом.
     *
     * @return Collection<int, CatalogManufacturerDTO>
     */
    public function execute(): Collection
    {
        return $this->manufacturers
            ->findAllWithAllowedVehicles()
            ->map(static fn (ManufacturerData $manufacturer): CatalogManufacturerDTO => CatalogManufacturerDTO::fromData($manufacturer))
            ->values();
    }
}
