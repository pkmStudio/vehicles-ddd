<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EnginesCodeImportInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @deprecated Фича группировки двигателей по кросс-кодам ещё на большой бизнес-доработке.
 *   Этот класс уже сейчас нигде не резолвится ни из одной точки входа (только биндинг в
 *   ImportServiceProvider) — оставлен как заготовка, не удалять до решения по фиче в целом.
 */
final class EnginesCodeImport implements EnginesCodeImportInterface, ToCollection
{
    /**
     * Прочитать первый лист файла кодов двигателей.
     *
     * Шаги:
     * 1) Передать текущий reader в Laravel Excel.
     * 2) Получить коллекции листов из файла.
     * 3) Вернуть первую коллекцию строк.
     */
    public function parse(string $path): Collection
    {
        return Excel::toCollection($this, $path)->first();
    }

    /**
     * Вернуть строки листа без преобразования.
     *
     * Шаги:
     * 1) Принять коллекцию строк от Laravel Excel.
     * 2) Вернуть её как результат чтения deprecated reader-а.
     */
    public function collection(Collection $collection)
    {
        return $collection;
    }
}
