<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmReadRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm\ListNomenclaturesForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Оркестрирует CRM read-сценарии списка и справочных options Warehouse-номенклатуры.
 */
final readonly class ListNomenclaturesForCrmUseCase implements ListNomenclaturesForCrmUseCaseInterface
{
    public function __construct(
        private NomenclatureCrmReadRepositoryInterface $nomenclatures,
    ) {}

    public function execute(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO
    {
        return $this->nomenclatures->paginate($query);
    }

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->nomenclatures->typeOptions($query, $id, $limit);
    }

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function brands(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->nomenclatures->brandOptions($query, $id, $limit);
    }
}
