<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\NomenclatureCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\ListNomenclaturesForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\SearchNomenclaturesForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\ShowNomenclatureForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Read-only клиент CRM сценариев складской номенклатуры.
 */
final readonly class NomenclatureCrmClient implements NomenclatureCrmClientInterface
{
    /**
     * Инициализирует CRM клиент номенклатуры.
     *
     * Шаги:
     * 1. Получает use case списка, detail и поиска номенклатур.
     * 2. Сохраняет зависимости как thin facade CRM read API.
     */
    public function __construct(
        private ListNomenclaturesForCrmUseCaseInterface $listNomenclatures,
        private ShowNomenclatureForCrmUseCaseInterface $showNomenclature,
        private SearchNomenclaturesForCrmUseCaseInterface $searchNomenclatures,
    ) {}

    /**
     * Возвращает постраничный список номенклатур для CRM.
     *
     * Шаги:
     * 1. Принимает готовый read-query DTO.
     * 2. Делегирует запрос list use case.
     * 3. Возвращает DTO страницы без доступа к БД из клиент.
     */
    public function paginate(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO
    {
        return $this->listNomenclatures->execute($query);
    }

    /**
     * Возвращает detail-снимок номенклатуры для CRM.
     *
     * Шаги:
     * 1. Принимает внутренний id номенклатуры.
     * 2. Делегирует поиск detail use case.
     * 3. Возвращает DTO или `null`, если запись не найдена.
     */
    public function show(int $id): ?NomenclatureCrmListItemDTO
    {
        return $this->showNomenclature->execute($id);
    }

    /**
     * Возвращает compact search options номенклатур для CRM.
     *
     * Шаги:
     * 1. Принимает строку поиска и лимит результата.
     * 2. Делегирует поиск search use case.
     * 3. Возвращает collection DTO/options для presenter границу.
     */
    public function search(string $query, int $limit): Collection
    {
        return $this->searchNomenclatures->execute(
            query: $query,
            limit: $limit,
        );
    }

    /**
     * Возвращает type options для CRM-формы номенклатуры.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует запрос options list use case.
     * 3. Возвращает collection DTO без дополнительного mapping.
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->listNomenclatures->types(
            query: $query,
            id: $id,
            limit: $limit,
        );
    }

    /**
     * Возвращает brand options для CRM-формы номенклатуры.
     *
     * Шаги:
     * 1. Принимает необязательную строку поиска, выбранный id и лимит.
     * 2. Делегирует запрос options list use case.
     * 3. Возвращает collection DTO без дополнительного mapping.
     */
    public function brands(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->listNomenclatures->brands(
            query: $query,
            id: $id,
            limit: $limit,
        );
    }
}
