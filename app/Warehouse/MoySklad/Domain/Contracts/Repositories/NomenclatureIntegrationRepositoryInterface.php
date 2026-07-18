<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Domain\Contracts\Repositories;

use App\Warehouse\MoySklad\Domain\ModelData\NomenclatureIntegrationData;

/**
 * Порт чтения integration-state МойСклад.
 */
interface NomenclatureIntegrationRepositoryInterface
{
    /**
     * Возвращает существующую связь или создаёт pending-связь для номенклатуры.
     */
    public function firstOrCreateForNomenclature(int $nomenclatureId): NomenclatureIntegrationData;

    /**
     * Возвращает связь номенклатуры с МойСклад или null.
     */
    public function firstForNomenclature(int $nomenclatureId): ?NomenclatureIntegrationData;

    /**
     * Находит связь для delete workflow по явному id, локальному id или externalCode.
     */
    public function findForDelete(int $nomenclatureId, string $externalCode, ?int $integrationId = null): ?NomenclatureIntegrationData;
}
