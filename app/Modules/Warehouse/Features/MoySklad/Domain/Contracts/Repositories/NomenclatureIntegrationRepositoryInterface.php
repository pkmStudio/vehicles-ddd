<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;

/**
 * Порт чтения integration-state МойСклад.
 */
interface NomenclatureIntegrationRepositoryInterface
{
    /**
     * Возвращает integration-state МойСклад по id номенклатуры или null.
     */
    public function findByNomenclatureId(int $nomenclatureId): ?NomenclatureIntegrationData;

    /**
     * Возвращает integration-state для удаления по сохранённой связке или fallback external_code.
     */
    public function findForDeletion(
        int $nomenclatureId,
        string $externalCode,
        ?int $integrationId = null,
    ): ?NomenclatureIntegrationData;
}
