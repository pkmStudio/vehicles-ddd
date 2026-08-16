<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM search-сценарий Warehouse-номенклатуры.
 */
final readonly class SearchNomenclaturesForCrmUseCase
{
    /**
     * Инициализирует зависимости сценария.
     *
     * Шаги:
     * 1) Принять CRM repository номенклатуры.
     * 2) Использовать repository для compact autocomplete search.
     */
    public function __construct(
        private NomenclatureCrmRepositoryInterface $nomenclatures,
    ) {}

    /**
     * @return Collection<int, NomenclatureCrmSearchItemDTO>
     *
     * Шаги:
     * 1) Принять строку поиска и лимит результатов.
     * 2) Делегировать поиск CRM репозиторий.
     * 3) Вернуть compact DTO-коллекцию найденной номенклатуры.
     */
    public function execute(string $query, int $limit = 20): Collection
    {
        return $this->nomenclatures->search($query, $limit);
    }
}
