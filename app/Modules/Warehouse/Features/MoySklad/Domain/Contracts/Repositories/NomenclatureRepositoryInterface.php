<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;
use Generator;

/**
 * Порт чтения Warehouse-номенклатуры для MoySklad-фичи.
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по id или null.
     * Шаги:
     * 1) Найти Warehouse-номенклатуру по id.
     * 2) Загрузить данные, необходимые для payload МойСклад.
     * 3) Вернуть Data-снимок или null.
     */
    public function findById(int $id): ?NomenclatureData;

    /**
     * Итерирует номенклатуру курсором по id.
     * Шаги:
     * 1) Нормализовать размер чанка чтения.
     * 2) Читать Warehouse-номенклатуру ordered cursor-ом по id.
     * 3) Yield-ить Data-снимки по одному для backfill dispatcher-а.
     *
     * @return Generator<int, NomenclatureData>
     */
    public function cursorById(int $chunk): Generator;
}
