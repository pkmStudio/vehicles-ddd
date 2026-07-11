<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Engine;

use App\Vehicles\Import\Domain\Contracts\Imports\Command\EnginesCodeImportInterface;
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
    public function parse(string $path): Collection
    {
        return Excel::toCollection($this, $path)->first();
    }

    public function collection(Collection $collection)
    {
        return $collection;
    }
}
