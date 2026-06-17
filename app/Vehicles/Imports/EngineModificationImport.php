<?php

declare(strict_types=1);

namespace App\Vehicles\Imports;

use App\Vehicles\Models\Engine;
use App\Vehicles\Models\Modification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

class EngineModificationImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow
{
    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {
            $engine = Engine::query()->where('eng_id', $row[0])->first();
            $modification = Modification::query()->where('mod_id', $row[1])->where('type', $row[2])->first();

            if ($engine && $modification) {
                $engine->modifications()->syncWithoutDetaching([
                    $modification->id => [
                        'eng_id' => $row[0],
                        'mod_id' => $row[1],
                        'type' => $row[2],
                    ],
                ]);
            }
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Import failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    public function startRow(): int
    {
        return 2;
    }
}
