<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read-only клиент CRM сценариев складской номенклатуры.
 */
interface NomenclatureCrmClientInterface
{
    /**
     * Возвращает постраничный список номенклатур.
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть DTO страницы, совместимый с CRM границу.
     */
    public function paginate(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO;

    /**
     * Возвращает detail-снимок номенклатуры.
     *
     * Шаги:
     * 1. Принять внутренний id номенклатуры.
     * 2. Вернуть DTO или `null`, если запись не найдена.
     */
    public function show(int $id): ?NomenclatureCrmListItemDTO;

    /**
     * Возвращает compact search options номенклатур.
     *
     * Шаги:
     * 1. Принять строку поиска и лимит.
     * 2. Вернуть collection search DTO.
     *
     * @return Collection<int, NomenclatureCrmSearchItemDTO>
     */
    public function search(string $query, int $limit): Collection;

    /**
     * Возвращает type options для CRM-формы номенклатуры.
     *
     * Шаги:
     * 1. Принять необязательную строку поиска, выбранный id и лимит.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * Возвращает brand options для CRM-формы номенклатуры.
     *
     * Шаги:
     * 1. Принять необязательную строку поиска, выбранный id и лимит.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function brands(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
