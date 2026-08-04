<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use Illuminate\Support\Collection;

interface ListNomenclaturesForCrmUseCaseInterface
{
    public function execute(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO;

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function types(?string $query = null, ?int $id = null, int $limit = 50): Collection;

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function brands(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
