<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Meta-снимок пагинации CRM списка автомобилей.
 */
final readonly class VehicleCrmPaginationMetaDTO implements HttpArraySerializableInterface
{
    /**
     * Хранит числовые значения текущей страницы, размера и общего результата.
     */
    public function __construct(
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
    ) {}

    /**
     * Возвращает meta payload постраничного CRM read API.
     *
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage,
        ];
    }
}
