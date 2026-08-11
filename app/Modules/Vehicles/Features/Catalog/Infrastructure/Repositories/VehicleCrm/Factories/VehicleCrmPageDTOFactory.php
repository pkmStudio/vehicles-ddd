<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Собирает page DTO CRM списка автомобилей.
 */
final readonly class VehicleCrmPageDTOFactory
{
    /**
     * Инициализирует factory pagination meta DTO.
     *
     * Шаги:
     * 1. Получает factory meta DTO из контейнера.
     * 2. Сохраняет зависимость для сборки page DTO.
     */
    public function __construct(
        private VehicleCrmPaginationMetaDTOFactory $metaFactory,
    ) {}

    /**
     * Создает page DTO из элементов и Laravel paginator.
     *
     * Шаги:
     * 1. Принимает collection list item DTO.
     * 2. Собирает pagination meta DTO из paginator.
     * 3. Возвращает page DTO для CRM response boundary.
     *
     * @param  Collection<int, VehicleCrmListItemDTO>  $items
     */
    public function make(Collection $items, LengthAwarePaginator $paginator): VehicleCrmPageDTO
    {
        return new VehicleCrmPageDTO(
            data: $items,
            meta: $this->metaFactory->make($paginator),
        );
    }
}
