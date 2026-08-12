<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Nomenclature;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Порт построчного импорта Warehouse-номенклатуры из Excel-строки.
 */
interface ImportNomenclatureFromRowServiceInterface
{
    /**
     * Валидирует строку и пишет номенклатуру через явные create/update команды.
     *
     * Шаги:
     * 1) Нормализовать обязательные поля строки и найти type/brand.
     * 2) Собрать details через шаблон типа, если он задан.
     * 3) Сформировать NomenclatureData из строки импорта.
     * 4) Создать или обновить запись и отправить mutation event.
     *
     * @param  array<int, mixed>  $row
     * @param  Collection<int, TypeData>  $types
     * @param  Collection<int, BrandData>  $brands
     */
    public function importFromRow(
        array $row,
        Collection $types,
        Collection $brands,
        ?int $userId = null,
        ?string $operationId = null,
    ): NomenclatureData;
}
