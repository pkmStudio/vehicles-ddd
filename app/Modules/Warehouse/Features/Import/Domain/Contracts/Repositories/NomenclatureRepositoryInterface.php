<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

/**
 * Порт чтения уже сохранённой Warehouse-номенклатуры для Import-фичи.
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по id или null.
     *
     * Шаги:
     * 1) Выполнить чтение номенклатуры по первичному ключу.
     * 2) Вернуть NomenclatureData, если запись найдена.
     * 3) Вернуть null, если записи нет.
     */
    public function findById(int $id): ?NomenclatureData;

    /**
     * Возвращает номенклатуру по артикулу или null.
     *
     * Шаги:
     * 1) Выполнить чтение номенклатуры по part_number.
     * 2) Вернуть NomenclatureData для найденной записи.
     * 3) Вернуть null, если артикул отсутствует.
     */
    public function findByPartNumber(string $partNumber): ?NomenclatureData;

    /**
     * Возвращает найденные номенклатуры, индексированные по part_number. Отсутствующие в БД
     * артикулы просто не попадут в результат — вызывающий сам решает, как на это реагировать.
     *
     * Шаги:
     * 1) Принять список артикулов из строки набора.
     * 2) Прочитать все найденные номенклатуры одним запросом.
     * 3) Преобразовать записи в NomenclatureData.
     * 4) Вернуть collection, индексированную по part_number.
     *
     * @param  array<int, string>  $partNumbers
     * @return Collection<string, NomenclatureData>
     */
    public function findByPartNumbers(array $partNumbers): Collection;
}
