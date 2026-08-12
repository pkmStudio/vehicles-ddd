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
     * Шаги:
     * 1) Ограничить поиск provider=moysklad.
     * 2) Найти integration-state по nomenclature_id.
     * 3) Вернуть Data-снимок или null.
     */
    public function findByNomenclatureId(int $nomenclatureId): ?NomenclatureIntegrationData;

    /**
     * Возвращает integration-state для удаления по сохранённой связке или fallback external_code.
     * Шаги:
     * 1) Если передан integrationId, искать provider=moysklad запись по первичному ключу.
     * 2) Иначе искать provider=moysklad запись по nomenclature_id или external_code.
     * 3) Вернуть Data-снимок найденной связи или null.
     */
    public function findForDeletion(
        int $nomenclatureId,
        string $externalCode,
        ?int $integrationId = null,
    ): ?NomenclatureIntegrationData;
}
