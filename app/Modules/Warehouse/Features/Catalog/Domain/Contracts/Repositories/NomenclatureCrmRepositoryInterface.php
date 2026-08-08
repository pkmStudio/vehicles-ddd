<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Support\Collection;

interface NomenclatureCrmRepositoryInterface
{
    public function paginate(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO;

    public function findById(int $id): ?NomenclatureCrmListItemDTO;

    /**
     * @return Collection<int, NomenclatureCrmSearchItemDTO>
     */
    public function search(string $query, int $limit = 20): Collection;

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function typeOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function brandOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
