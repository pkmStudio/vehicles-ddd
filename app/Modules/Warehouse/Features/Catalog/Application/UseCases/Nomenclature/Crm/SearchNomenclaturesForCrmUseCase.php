<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\SearchNomenclaturesForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM search-сценарий Warehouse-номенклатуры.
 */
final readonly class SearchNomenclaturesForCrmUseCase implements SearchNomenclaturesForCrmUseCaseInterface
{
    public function __construct(
        private NomenclatureCrmRepositoryInterface $nomenclatures,
    ) {}

    /**
     * @return Collection<int, NomenclatureCrmSearchItemDTO>
     */
    public function execute(string $query, int $limit = 20): Collection
    {
        return $this->nomenclatures->search($query, $limit);
    }
}
