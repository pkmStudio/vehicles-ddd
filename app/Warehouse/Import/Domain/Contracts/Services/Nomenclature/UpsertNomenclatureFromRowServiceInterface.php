<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Services\Nomenclature;

use App\Warehouse\Import\Domain\ModelData\NomenclatureData;
use App\Warehouse\Import\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Порт построчного upsert Warehouse-номенклатуры из Excel-строки.
 */
interface UpsertNomenclatureFromRowServiceInterface
{
    /**
     * Резолвит type_id/brand_id по предзагруженным справочникам (полный TypeData нужен для
     * резолва detail-шаблона по char, не только id), собирает details через Templates и пишет
     * запись через Command.
     *
     * @param  array<int, mixed>  $row  сырая Excel-строка (0-based индексы)
     * @param  Collection<int, TypeData>  $types  предзагруженный справочник типов
     * @param  Collection<int, \App\Warehouse\Import\Domain\ModelData\BrandData>  $brands  предзагруженный справочник брендов
     *
     * @throws \InvalidArgumentException если тип/бренд/шаблон не резолвится
     */
    public function upsertFromRow(array $row, Collection $types, Collection $brands): NomenclatureData;
}
