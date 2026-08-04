<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;

interface ShowNomenclatureForCrmUseCaseInterface
{
    public function execute(int $id): ?NomenclatureCrmListItemDTO;
}
