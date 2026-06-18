<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * @deprecated
 */
final class EnginesCodeImport implements ToCollection
{
    public function collection(Collection $collection)
    {
        return $collection;
    }
}
