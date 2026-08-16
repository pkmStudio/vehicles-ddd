<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmPageDTO;

/**
 * Преобразует CRM DTO Warehouse-типов в HTTP response arrays.
 */
final readonly class TypeCrmReadPresenter
{
    /**
     * @return array{data: list<array{id: int, name: string, char: string, nomenclatures_count: int, created_at: string|null, updated_at: string|null}>, meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function page(TypeCrmPageDTO $page): array
    {
        return [
            'data' => $page->data
                ->map(fn (TypeCrmListItemDTO $type): array => $this->detail($type))
                ->values()
                ->all(),
            'meta' => $page->meta->toArray(),
        ];
    }

    /**
     * @return array{id: int, name: string, char: string, nomenclatures_count: int, created_at: string|null, updated_at: string|null}
     */
    public function detail(TypeCrmListItemDTO $detail): array
    {
        return $detail->toArray();
    }
}
