<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPageDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

/**
 * Преобразует CRM DTO комплектов в HTTP ответ arrays.
 */
final readonly class KitCrmReadPresenter
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
     * Преобразует DTO страницы комплектов в форму HTTP-ответа.
     *
     * Шаги:
     * 1. Берет элементы и meta из DTO страницы.
     * 2. Делегирует сборку стандартному HTTP array presenter.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(KitCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * Преобразует detail DTO комплекта в HTTP ответ item.
     *
     * Шаги:
     * 1. Принимает detail DTO.
     * 2. Делегирует механическую сериализацию стандартному HTTP array presenter.
     *
     * @return array<string, mixed>
     */
    public function detail(KitCrmListItemDTO $detail): array
    {
        return $this->arrays->item($detail);
    }
}
