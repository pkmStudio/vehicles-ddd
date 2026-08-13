<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

/**
 * Преобразует CRM DTO ТС в массивы HTTP-ответов.
 */
final readonly class VehicleCrmReadPresenter
{
    /**
     * Получает общий преобразователь DTO, поддерживающих toArray().
     *
     * Шаги:
     * - Сохранить преобразователь массивов для страницы и детального ответа.
     */
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * Возвращает HTTP-форму страницы ТС.
     *
     * Шаги:
     * - Преобразовать элементы страницы через общий presenter коллекций.
     * - Сохранить meta pagination рядом с data.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(VehicleCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * Возвращает HTTP-форму детального снимка ТС.
     *
     * Шаги:
     * - Преобразовать плоский DTO автомобиля в массив.
     *
     * @return array<string, mixed>
     */
    public function detail(VehicleCrmListItemDTO $detail): array
    {
        return $this->arrays->item($detail);
    }

    /**
     * Возвращает HTTP-форму страницы relation DTO.
     *
     * Шаги:
     * 1. Преобразует relation DTO items через общий presenter.
     * 2. Сохраняет pagination meta рядом с data.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function relationPage(VehicleCrmRelationPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * Возвращает HTTP-форму страницы модификаций без вложенных двигателей.
     *
     * Шаги:
     * 1. Собирает стандартную HTTP-форму relation page.
     * 2. Удаляет legacy-вложение `engines` из каждой модификации.
     * 3. Возвращает плоскую страницу модификаций.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function modificationsPage(VehicleCrmRelationPageDTO $page): array
    {
        $response = $this->relationPage($page);
        $response['data'] = array_map(static function (array $modification): array {
            unset($modification['engines']);

            return $modification;
        }, $response['data']);

        return $response;
    }
}
