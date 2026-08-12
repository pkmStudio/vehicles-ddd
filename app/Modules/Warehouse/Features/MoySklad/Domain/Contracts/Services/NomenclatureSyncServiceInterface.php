<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Services;

/**
 * Порт синхронизации Warehouse-номенклатуры с товарами МойСклад.
 */
interface NomenclatureSyncServiceInterface
{
    /**
     * Синхронизирует одну локальную номенклатуру с товаром МойСклад.
     * Шаги:
     * 1) Загрузить Warehouse-номенклатуру и integration-state.
     * 2) Собрать product folder, payload и payload hash.
     * 3) Создать или обновить товар МойСклад.
     * 4) Записать synced или failed state.
     */
    public function sync(int $nomenclatureId): void;

    /**
     * Удаляет товар МойСклад для локально удалённой номенклатуры.
     * Шаги:
     * 1) Найти integration-state удаления по local/external identifiers.
     * 2) Определить id товара МойСклад через saved id или fallback search.
     * 3) Удалить найденный товар во внешнем client.
     * 4) Записать deleted или failed state.
     */
    public function delete(int $nomenclatureId, string $partNumber, ?string $externalId = null, ?int $integrationId = null): void;
}
