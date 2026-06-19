<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Imports;

use App\Vehicles\Domain\Contracts\Imports\EnginesCodeImportInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @deprecated
 */
final class EnginesCodeImport implements ToCollection, EnginesCodeImportInterface
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
