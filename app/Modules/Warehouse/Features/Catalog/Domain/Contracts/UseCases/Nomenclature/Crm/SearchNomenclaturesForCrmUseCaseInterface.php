<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use Illuminate\Support\Collection;

interface SearchNomenclaturesForCrmUseCaseInterface
{
    /**
     * @return Collection<int, NomenclatureCrmSearchItemDTO>
     *
     * Шаги:
     * 1) Принять строку поиска и лимит результатов.
     * 2) Делегировать поиск CRM репозиторий.
     * 3) Вернуть compact DTO-коллекцию найденной номенклатуры.
     */
    public function execute(string $query, int $limit = 20): Collection;
}
