<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmPageDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

/**
 * Преобразует CRM DTO упаковочных размеров в HTTP response arrays.
 */
final readonly class PackDimensionCrmReadPresenter
{
    /**
     * Инициализирует общий array presenter.
     *
     * Шаги:
     * 1. Получает presenter стандартных item/page shapes.
     * 2. Сохраняет его для преобразования DTO в массивы.
     */
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Преобразует page DTO упаковочных размеров в HTTP response shape.
     *
     * Шаги:
     * 1. Берет элементы и meta из page DTO.
     * 2. Делегирует сборку стандартному HTTP array presenter.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(PackDimensionCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * Преобразует detail DTO упаковочного размера в HTTP response item.
     *
     * Шаги:
     * 1. Принимает detail DTO.
     * 2. Делегирует механическую сериализацию стандартному HTTP array presenter.
     *
     * @return array<string, mixed>
     */
    public function detail(PackDimensionCrmListItemDTO $detail): array
    {
        return $this->arrays->item($detail);
    }
}
