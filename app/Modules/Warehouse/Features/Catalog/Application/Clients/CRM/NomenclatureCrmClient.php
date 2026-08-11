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

final readonly class NomenclatureCrmClient implements NomenclatureCrmClientInterface
{
    public function __construct(
        private ListNomenclaturesForCrmUseCaseInterface $listNomenclatures,
        private ShowNomenclatureForCrmUseCaseInterface $showNomenclature,
        private SearchNomenclaturesForCrmUseCaseInterface $searchNomenclatures,
    ) {}

    public function paginate(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO
    {
        return $this->listNomenclatures->execute($query);
    }

    public function show(int $id): ?NomenclatureCrmListItemDTO
    {
        return $this->showNomenclature->execute($id);
    }

    public function search(string $query, int $limit): Collection
    {
        return $this->searchNomenclatures->execute(
            query: $query,
            limit: $limit,
        );
    }

    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->listNomenclatures->types(
            query: $query,
            id: $id,
            limit: $limit,
        );
    }

    public function brands(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->listNomenclatures->brands(
            query: $query,
            id: $id,
            limit: $limit,
        );
    }
}
