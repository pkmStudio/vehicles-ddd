<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

final readonly class NomenclatureCrmReadPresenter
{
    /**
     * Получает общий presenter для преобразования DTO в HTTP arrays.
     *
     * Шаги:
     * 1) Принять HttpArrayPresenter из support layer.
     * 2) Использовать его для page/detail CRM responses.
     */
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     *
     * Шаги:
     * 1) Принять DTO страницы CRM-результата.
     * 2) Преобразовать элементы и meta в массив ответа.
     * 3) Вернуть структуру, готовую для JSON ответ.
     */
    public function page(NomenclatureCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * @return array<string, mixed>
     *
     * Шаги:
     * 1) Принять nullable DTO детальной CRM-проекции.
     * 2) Вернуть null для отсутствующей записи.
     * 3) Преобразовать найденный DTO в массив ответа.
     */
    public function detail(NomenclatureCrmListItemDTO $detail): array
    {
        return $this->arrays->item($detail);
    }
}
