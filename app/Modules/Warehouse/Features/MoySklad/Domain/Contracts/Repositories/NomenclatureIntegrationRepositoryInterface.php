<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\NomenclatureIntegrationLookupDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;

/**
 * Порт чтения integration-state МойСклад.
 */
interface NomenclatureIntegrationRepositoryInterface
{
    /**
     * Возвращает integration-state МойСклад по typed lookup-критерию или null.
     */
    public function find(NomenclatureIntegrationLookupDTO $lookup): ?NomenclatureIntegrationData;
}
