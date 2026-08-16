<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
use App\Support\Http\Contracts\HttpArraySerializableInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Собирает page DTO relation endpoints CRM автомобиля.
 */
final readonly class VehicleCrmRelationPageDTOFactory
{
    public function __construct(
        private VehicleCrmPaginationMetaDTOFactory $metaFactory,
    ) {}

    /**
     * Собирает DTO страницы связанных CRM-данных автомобиля.
     *
     * Шаги:
     * 1. Принимает collection DTO элементов и Laravel paginator.
     * 2. Собирает pagination meta через локальную factory.
     * 3. Возвращает общий relation page DTO.
     *
     * @param  Collection<int, HttpArraySerializableInterface>  $items
     */
    public function make(Collection $items, LengthAwarePaginator $paginator): VehicleCrmRelationPageDTO
    {
        return new VehicleCrmRelationPageDTO(
            data: $items,
            meta: $this->metaFactory->make($paginator),
        );
    }
}
